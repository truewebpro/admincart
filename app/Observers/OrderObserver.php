<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\OrderLog;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        //
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
}
