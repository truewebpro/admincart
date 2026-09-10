<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefundOrderRequest;
use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Payments\ChargeResolver;
use App\Services\RefundService;
use App\Support\ResolvesCurrentShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    use ResolvesCurrentShop;

    public function __construct(
        private readonly RefundService $refunds,
        private readonly ChargeResolver $charges,
    ) {
    }

    /**
     * Everything the refund dialog needs. Read-only: describe() works out what
     * a refund would act on without writing the charge row, so opening the
     * dialog and closing it again leaves no trace.
     */
    public function show(Order $order): JsonResponse
    {
        $shopId = $this->currentShopId();
        $this->assertBelongsToCurrentShop($order);

        try {
            $details = $this->charges->describe($shopId, $order);
        } catch (ValidationException $e) {
            // Not refundable through this module — tell the dialog why.
            return response()->json([
                'order'   => $this->orderPayload($order),
                'charge'  => null,
                'history' => [],
                'message' => collect($e->errors())->flatten()->first(),
            ]);
        }

        $charge    = $details['charge'];
        $refunded  = $charge?->refundedMinor() ?? 0;
        $total     = $charge?->amount_minor ?? $details['amountMinor'];

        return response()->json([
            'order'  => $this->orderPayload($order),
            'charge' => [
                'id'         => $charge?->id,
                'provider'   => $details['provider'],
                'amount'     => $total / 100,
                'refunded'   => $refunded / 100,
                'refundable' => max(0, $total - $refunded) / 100,
                'settled'    => $charge ? (bool) data_get($charge->meta, 'settled', false) : true,
                'reference'  => $details['reference'],
                // True until the first refund writes the ledger row.
                'adopted'    => $details['adopted'],
            ],
            'history' => PaymentTransaction::query()
                ->forShop($shopId)
                ->where('order_id', $order->getKey())
                ->whereIn('type', [PaymentTransaction::TYPE_REFUND, PaymentTransaction::TYPE_VOID])
                ->latest('id')
                ->get()
                ->map(fn (PaymentTransaction $t) => [
                    'id'         => $t->id,
                    'type'       => $t->type,
                    'status'     => $t->status,
                    'amount'     => $t->amount_minor / 100,
                    'reason'     => $t->reason,
                    'error'      => $t->error_message,
                    'created_at' => $t->created_at?->toIso8601String(),
                ]),
        ]);
    }

    public function store(RefundOrderRequest $request, Order $order): JsonResponse
    {
        $shopId = $this->currentShopId();
        $this->assertBelongsToCurrentShop($order);

        $transaction = $this->refunds->process(
            shopId: $shopId,
            order: $order,
            amountMinor: $request->amountMinor(),
            action: $request->input('action', 'auto'),
            reason: $request->input('reason'),
            idempotencyKey: $request->input('idempotency_key'),
            userId: $request->user()->id,
        );

        $failed = $transaction->status === PaymentTransaction::STATUS_FAILED;

        return response()->json([
            'status'  => $transaction->status,
            'type'    => $transaction->type,
            'amount'  => $transaction->amount_minor / 100,
            'message' => $failed
                ? $transaction->error_message
                : ($transaction->status === PaymentTransaction::STATUS_PENDING
                    ? 'Refund sent. The provider will confirm it shortly.'
                    : 'Refunded.'),
            'transaction_id' => $transaction->id,
        ], $failed ? 422 : 201);
    }

    private function orderPayload(Order $order): array
    {
        return [
            'id'       => $order->getKey(),
            'number'   => $order->order_number ?? $order->getKey(),
            'currency' => $order->currency ?? config('payments.default_currency', 'GBP'),
        ];
    }
}
