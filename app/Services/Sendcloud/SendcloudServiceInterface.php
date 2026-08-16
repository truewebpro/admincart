<?php

namespace App\Services\Sendcloud;

use App\Exceptions\SendcloudApiException;

interface SendcloudServiceInterface
{
    /**
     * Create (and, depending on payload, announce/label) a parcel with Sendcloud.
     *
     * @param array $payload Raw input from the request — shape differs between
     *                       v2 (flat, shipping_method) and v3 (to_address/from_address,
     *                       shipping_option_code), each implementation handles its own mapping.
     *
     * @return array Normalized result, always containing at least:
     *               - id (parcel id)
     *               - order_number
     *               - tracking_number
     *               - shipping_method (kept for backward compatibility with Order::shipment_id)
     *               - shipment (array with at least 'name')
     *
     * @throws SendcloudApiException
     */
    public function createParcel(array $payload): array;
}
