<?php

namespace App\Services\Sendcloud;

class SendcloudStatusMapper
{
    /**
     * Label creation lifecycle — used right after createParcel(), and by the webhook
     * for the "Being announced / Ready to send / Parcel en route" label-relevant statuses.
     */
    public static function toLabelStatus(?string $statusCode, ?string $statusMessage): string
    {
        return match ($statusCode) {
            'ANNOUNCING' => 'pending',
            'READY_TO_SEND' => 'created',
            'ANNOUNCEMENT_FAILED' => 'failed',
            default => match ($statusMessage) {
                'Being announced' => 'pending',
                'Ready to send' => 'created',
                'Parcel en route' => 'printed',
                'Cancelled' => 'cancelled',
                default => 'pending',
            },
        };
    }

    /**
     * Post-shipment delivery tracking — from carrier tracking updates/webhooks.
     * Store this in a separate `tracking_status` column, not label_status.
     */
    public static function toTrackingStatus(?string $statusMessage): ?string
    {
        return match ($statusMessage) {
            'Being announced' => 'label_created',
            'Ready to send' => 'label_created',
            'Shipment picked up by driver' => 'collected',
            'Driver en route' => 'collected',
            'Error collecting' => 'collection_failed',
            'Parcel en route' => 'in_transit',
            'Not sorted' => 'in_transit',
            'Being sorted' => 'in_transit',
            'Sorted' => 'in_transit',
            'Delivery delayed' => 'delayed',
            'Awaiting customer pickup' => 'awaiting_pickup',
            'Delivery attempt failed' => 'delivery_attempt_failed',
            'Unable to deliver' => 'delivery_failed',
            'Delivered' => 'delivered',
            'Shipment collected by customer' => 'delivered',
            'Cancelled' => 'cancelled',
            default => null, // unmapped/unknown carrier status — leave as-is, log for visibility
        };
    }
}
