<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesShopContext;
use App\Http\Controllers\Controller;
use App\Models\CustomerShop;
use App\Models\Order;
use App\Services\StoreCreditService;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Admin routes — behind web session auth (auth:web). Scoped via currentShopId()
 * (see ResolvesShopContext trait), which your login / shop-switch flow always
 * keeps set in session('shop_id') (an admin can belong to multiple shops via
 * shop_users and switch between them, so this reads from session rather than
 * a fixed column on the user).
 *
 * Route param is {customerShop}, bound by cshop_id (CustomerShop's primary key),
 * NOT {customer} — a customer can have a different balance at every shop, so
 * admin always operates on the customer_shops row for THIS shop specifically.
 * Your customer search UI should resolve to a cshop_id (e.g. search customers,
 * then look up/create their customer_shops row for currentShopId())
 * before hitting these routes.
 */
class StoreCreditController extends Controller
{
    use ResolvesShopContext;

    public function __construct(protected StoreCreditService $storeCreditService)
    {
    }

    // GET /admin/customer-shops/{customerShop}/store-credit
    public function show(CustomerShop $customerShop)
    {
        $this->authorizeShop($customerShop);

        return response()->json([
            'balance' => (float) $customerShop->store_credit_balance,
            'history' => $this->storeCreditService->history($customerShop, 25),
        ]);
    }

    // POST /admin/customer-shops/{customerShop}/store-credit/adjust
    // Body: { type: 'credit'|'debit', amount: number, notes: string (required) }
    public function adjust(Request $request, CustomerShop $customerShop)
    {
        $this->authorizeShop($customerShop);

        $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:1000', // notes are mandatory for manual adjustments
        ]);

        try {
            $transaction = $request->type === 'credit'
                ? $this->storeCreditService->credit(
                    customerShop: $customerShop,
                    amount: $request->amount,
                    source: 'manual_admin',
                    notes: $request->notes,
                    createdByType: 'admin',
                    createdByAdminId: auth()->id(),
                )
                : $this->storeCreditService->debit(
                    customerShop: $customerShop,
                    amount: $request->amount,
                    source: 'manual_admin',
                    notes: $request->notes,
                    createdByType: 'admin',
                    createdByAdminId: auth()->id(),
                );
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Store credit updated.',
            'balance' => (float) $customerShop->fresh()->store_credit_balance,
            'transaction' => $transaction,
        ]);
    }

    /**
     * POST /admin/orders/{order}/refund-as-credit
     * Body: { amount: number, notes: string }
     * Used instead of a cash refund — issues store credit for a partial or full
     * refund. Resolves the CustomerShop from the order's customer_id + shop_id
     * (replace with $order->cshop_id directly if your Order model already carries it).
     *
     * Strict lookup, not firstOrCreate — an order can only exist after checkout,
     * and checkout always creates the customer_shops row first. A missing row
     * here means something is wrong with the order's data, not a legitimate
     * first-time visit — see the same reasoning in LoyaltyService::earnFromOrder().
     */
    public function refundAsCredit(Request $request, Order $order)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'required|string|max:1000',
        ]);

        abort_unless($order->shop_id === $this->currentShopId(), 403);

        $customerShop = CustomerShop::where('customer_id', $order->customer_id)
            ->where('shop_id', $order->shop_id)
            ->first();

        abort_unless($customerShop, 422, 'No customer_shops record found for this order — cannot issue credit.');

        // Store credit is the only refund path (no cash refunds tracked separately).
        $remaining = $this->storeCreditService->remainingRefundable($order);

        if ($request->amount > $remaining) {
            return response()->json([
                'message' => "This order has £{$remaining} left refundable. Requested amount exceeds that.",
            ], 422);
        }

        $source = $request->amount >= $remaining ? 'order_refund_full' : 'order_refund_partial';

        $transaction = $this->storeCreditService->credit(
            customerShop: $customerShop,
            amount: $request->amount,
            source: $source,
            notes: $request->notes,
            order: $order,
            createdByType: 'admin',
            createdByAdminId: auth()->id(),
        );

        return response()->json([
            'message' => 'Refund issued as store credit.',
            'balance' => (float) $customerShop->fresh()->store_credit_balance,
            'remaining_refundable' => $this->storeCreditService->remainingRefundable($order),
            'transaction' => $transaction,
        ]);
    }

    protected function authorizeShop(CustomerShop $customerShop): void
    {
        abort_unless($customerShop->shop_id === $this->currentShopId(), 403);
    }
}
