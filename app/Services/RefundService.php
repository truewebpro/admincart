<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Payments\ChargeResolver;
use App\Payments\Contracts\PaymentGatewayInterface;
use App\Payments\DTO\GatewayResponse;
use App\Payments\DTO\RefundRequest;
use App\Payments\Exceptions\GatewayException;
use App\Payments\PaymentGatewayManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly ChargeResolver $charges,
    ) {
    }

    /**
     * $shopId is passed in rather than read off the order, so the session shop
     * stays the single authority on tenancy.
     *
     * @param  string  $action  auto | refund | cancel
     */
    public function process(
        int $shopId,
        Order $order,
        ?int $amountMinor = null,
        string $action = 'auto',
        ?string $reason = null,
        ?string $idempotencyKey = null,
        ?int $userId = null,
    ): PaymentTransaction {
        // Creates the charge row from orders/viva_payments the first time an
        // older order is refunded; a no-op once one exists.
        $charge = $this->charges->resolve($shopId, $order);
        $key    = $idempotencyKey ?: (string) Str::uuid();

        // Same key replayed -> hand back the original outcome, don't charge twice.
        if ($existing = $this->findByKey($shopId, $key)) {
            return $existing;
        }

        $lock = Cache::lock("refund:charge:{$charge->id}", 15);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'amount' => 'Another refund for this order is still processing. Try again in a moment.',
            ]);
        }

        try {
            $amountMinor ??= $charge->refundableMinor();
            $this->assertRefundable($charge, $amountMinor);

            $resolvedAction = $this->resolveAction($charge, $action);
            $gateway        = $this->gateways->driver($shopId, $charge->provider);

            if ($resolvedAction === 'cancel'
                && ! $gateway->supportsPartialCancel()
                && $amountMinor !== $charge->amount_minor) {
                throw ValidationException::withMessages([
                    'amount' => 'This provider can only cancel the full amount before settlement. '
                        . 'Refund the order instead once it has settled.',
                ]);
            }

            $request = RefundRequest::fromCharge($charge, $amountMinor, $key, $reason);

            // Written before the call so a timeout still leaves a trace to reconcile.
            $attempt = DB::transaction(fn () => PaymentTransaction::create([
                'shop_id'         => $shopId,
                'order_id'        => $order->getKey(),
                'parent_id'       => $charge->id,
                'provider'        => $charge->provider,
                'type'            => $resolvedAction === 'cancel'
                    ? PaymentTransaction::TYPE_VOID
                    : PaymentTransaction::TYPE_REFUND,
                'status'          => PaymentTransaction::STATUS_PENDING,
                'amount_minor'    => $amountMinor,
                'currency'        => $charge->currency,
                'idempotency_key' => $key,
                'reason'          => $reason,
                'request_payload' => ['action' => $resolvedAction, 'amount_minor' => $amountMinor],
                'created_by'      => $userId,
            ]));

            $response = $this->call($gateway, $resolvedAction, $request, $attempt);

            return $this->finalise($order, $attempt, $response);
        } finally {
            $lock->release();
        }
    }

    private function call(
        PaymentGatewayInterface $gateway,
        string $action,
        RefundRequest $request,
        PaymentTransaction $attempt,
    ): GatewayResponse {
        try {
            return $action === 'cancel'
                ? $gateway->cancel($request)
                : $gateway->refund($request);
        } catch (GatewayException $e) {
            return GatewayResponse::failure($e->errorCode, $e->getMessage(), $e->context);
        } catch (\Throwable $e) {
            Log::error('Refund call failed', [
                'transaction_id' => $attempt->id,
                'provider'       => $attempt->provider,
                'exception'      => $e->getMessage(),
            ]);

            return GatewayResponse::failure(
                'gateway_unreachable',
                'Could not reach the payment provider. The refund was not sent — check the order before retrying.'
            );
        }
    }

    private function finalise(Order $order, PaymentTransaction $attempt, GatewayResponse $response): PaymentTransaction
    {
        return DB::transaction(function () use ($order, $attempt, $response) {
            $attempt->forceFill([
                'status' => $response->successful
                    ? ($response->status === 'pending'
                        ? PaymentTransaction::STATUS_PENDING
                        : PaymentTransaction::STATUS_SUCCEEDED)
                    : PaymentTransaction::STATUS_FAILED,
                'gateway_transaction_id' => $response->transactionId,
                'error_code'             => $response->errorCode,
                'error_message'          => $response->errorMessage,
                'response_payload'       => $response->raw,
                'meta'                   => $response->meta ?: $attempt->meta,
            ])->save();

            if ($response->successful) {
                $charge = $attempt->parent;

                $order->forceFill([
                    'refunded_minor' => $charge->refundedMinor(),
                    'payment_status' => $charge->refundableMinor() === 0 ? 'refunded' : 'partially_refunded',
                ])->save();
            }

            return $attempt->fresh();
        });
    }

    private function assertRefundable(PaymentTransaction $charge, int $amountMinor): void
    {
        if ($amountMinor <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter an amount greater than zero.',
            ]);
        }

        $available = $charge->refundableMinor();

        if ($amountMinor > $available) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Only %s %s is left to refund on this payment.',
                    number_format($available / 100, 2),
                    $charge->currency
                ),
            ]);
        }
    }

    /** Before settlement a cancellation is cheaper and reversible; after it, refund. */
    private function resolveAction(PaymentTransaction $charge, string $requested): string
    {
        if (in_array($requested, ['refund', 'cancel'], true)) {
            return $requested;
        }

        return data_get($charge->meta, 'settled', false) ? 'refund' : 'cancel';
    }

    private function findByKey(int $shopId, string $key): ?PaymentTransaction
    {
        return PaymentTransaction::query()
            ->forShop($shopId)
            ->where('idempotency_key', $key)
            ->first();
    }
}
