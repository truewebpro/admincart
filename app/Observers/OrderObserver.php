<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderLog;
use App\Payments\ChargeResolver;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    public function __construct(private readonly ChargeResolver $charges)
    {
    }

    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        $this->syncCharge($order);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('order_status')) {
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'status_updated',
                'description' => 'Order status changed',
                'meta' => [
                    'from' => $order->getOriginal('order_status'),
                    'to' => $order->order_status,
                ],
                'source' => 'system'
            ]);
        }

        if ($order->isDirty('payment_status')) {
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'payment_status_updated',
                'description' => 'Payment status changed',
                'meta' => [
                    'from' => $order->getOriginal('payment_status'),
                    'to' => $order->payment_status,
                ],
            ]);
        }

        if ($order->isDirty('fulfillment_status')) {
            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'fulfillment_status_updated',
                'description' => 'Fulfillment status changed',
                'meta' => [
                    'from' => $order->getOriginal('fulfillment_status'),
                    'to' => $order->fulfillment_status,
                ],
            ]);
        }

        // Covers orders marked paid after the fact, and references that arrive
        // late — Viva writes viva_payments from its webhook, which can land
        // either side of the order being created.
        if ($order->isDirty(['payment_status', 'checkout_id', 'order_total'])) {
            $this->syncCharge($order);
        }
    }

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }

    private function syncCharge(Order $order): void
    {
        if ($order->payment_status !== 'paid') {
            return;
        }

        try {
            $this->charges->sync((int) $order->shop_id, $order);
        } catch (\Throwable $e) {
            Log::warning('Could not record the charge for an order', [
                'order_id' => $order->order_id,
                'shop_id'  => $order->shop_id,
                'reason'   => $e->getMessage(),
            ]);
        }
    }

}
