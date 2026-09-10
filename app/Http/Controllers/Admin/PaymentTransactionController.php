<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Payments\ChargeResolver;
use App\Payments\GatewaySchema;
use App\Support\ResolvesCurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentTransactionController extends Controller
{
    use ResolvesCurrentShop;

    public function __construct(private readonly ChargeResolver $charges)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $shopId = $this->currentShopId();

        $filters = $request->validate([
            'provider' => ['nullable', 'in:' . implode(',', GatewaySchema::providers())],
            'type'     => ['nullable', 'in:charge,refund,void'],
            'status'   => ['nullable', 'in:pending,succeeded,failed'],
            'order_id' => ['nullable', 'integer'],
            'search'   => ['nullable', 'string', 'max:120'],
            'from'     => ['nullable', 'date'],
            'to'       => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        // Scoped to one order: adopt its payment first, so an order paid before
        // this module existed still shows a charge row. sync() is idempotent
        // and stays quiet when the order was paid by an unsupported method.
        $synced = null;

        if ($orderId = ($filters['order_id'] ?? null)) {
            /** @var \App\Models\Order|null $order */
            $order = Order::query()
                ->where('shop_id', $shopId)
                ->whereKey($orderId)
                ->first();

            if ($order) {
                $synced = $this->charges->sync($shopId, $order);
            }
        }

        $query = PaymentTransaction::query()
            ->forShop($shopId)
            ->when($filters['provider'] ?? null, fn ($q, $v) => $q->where('provider', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['order_id'] ?? null, fn ($q, $v) => $q->where('order_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($sub) => $sub->where('gateway_transaction_id', 'like', "%{$v}%")
                    ->orWhere('idempotency_key', 'like', "%{$v}%")
            ))
            ->latest('id');

        $page = $query->paginate($filters['per_page'] ?? 25);

        return response()->json([
            'rows'  => collect($page->items())->map(fn (PaymentTransaction $t) => $this->row($t)),
            'total' => $page->total(),
            'page'  => $page->currentPage(),
            'stats' => $this->stats($shopId),
            // Lets the UI say why a single-order view is empty.
            'sync'  => $orderId ?? null ? [
                'attempted' => true,
                'charge_id' => $synced?->id,
                'message'   => $synced
                    ? null
                    : 'No refundable payment could be matched to this order. It may have been '
                    . 'paid by a method this module does not handle, or its payment reference is missing.',
            ] : null,
        ]);
    }

    /**
     * Full detail including the raw provider exchange. No credentials pass
     * through here: request_payload holds only the action and amount, and
     * auth headers and signatures are never persisted.
     */
    public function show(int $transaction): JsonResponse
    {
        $row = PaymentTransaction::query()
            ->forShop($this->currentShopId())
            ->find($transaction);

        abort_if($row === null, 404);

        return response()->json([
            'transaction' => array_merge($this->row($row), [
                'idempotency_key'  => $row->idempotency_key,
                'parent_id'        => $row->parent_id,
                'created_by'       => $row->created_by,
                'request_payload'  => $row->request_payload,
                'response_payload' => $row->response_payload,
                'meta'             => $row->meta,
            ]),
        ]);
    }

    /** Counts for the header strip, so a failing provider is obvious at a glance. */
    private function stats(int $shopId): array
    {
        return PaymentTransaction::query()
            ->forShop($shopId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('provider, status, count(*) as total')
            ->groupBy('provider', 'status')
            ->get()
            ->groupBy('provider')
            ->map(fn ($rows) => [
                'succeeded' => (int) $rows->firstWhere('status', 'succeeded')?->total,
                'pending'   => (int) $rows->firstWhere('status', 'pending')?->total,
                'failed'    => (int) $rows->firstWhere('status', 'failed')?->total,
            ])
            ->all(); // groupBy/map returns a Collection; the signature wants an array.
    }

    private function row(PaymentTransaction $t): array
    {
        return [
            'id'                     => $t->id,
            'order_id'               => $t->order_id,
            'provider'               => $t->provider,
            'type'                   => $t->type,
            'status'                 => $t->status,
            'amount'                 => $t->amount_minor / 100,
            'currency'               => $t->currency,
            'gateway_transaction_id' => $t->gateway_transaction_id,
            'reason'                 => $t->reason,
            'error_code'             => $t->error_code,
            'error_message'          => $t->error_message,
            'created_at'             => $t->created_at?->toIso8601String(),
        ];
    }
}
