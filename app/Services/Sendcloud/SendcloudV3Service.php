<?php

namespace App\Services\Sendcloud;

use App\Exceptions\SendcloudApiException;
use App\Models\Sendcloud;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendcloudV3Service implements SendcloudServiceInterface
{
    public function __construct(private Sendcloud $sendcloud) {}

    public function createParcel(array $payload): array
    {
        $body = array_filter([
            'order_number' => $payload['order_number'] ?? null,
            'to_address' => $this->buildToAddress($payload),
            'from_address' => $this->getSenderAddress(),
            'ship_with' => [
                'type' => 'shipping_option_code',
                'properties' => [
                    'shipping_option_code' => $payload['shipping_option_code'] ?? null,
                ],
            ],
            'total_order_price' => isset($payload['total_order_value']) ? [
                'value' => (string) $payload['total_order_value'],
                'currency' => $payload['total_order_value_currency'] ?? 'GBP',
            ] : null,
            'parcels' => [$this->buildParcel($payload)],
            'request_label' => true,
        ], fn($v) => $v !== null);

        $response = Http::withBasicAuth($this->sendcloud->public_key, $this->sendcloud->secret_key)
            ->post('https://panel.sendcloud.sc/api/v3/shipments/announce', $body);

        if (!$response->successful()) {
            $errorBody = $response->json() ?? ['raw' => $response->body()];

            Log::error('Sendcloud v3 request failed', [
                'status' => $response->status(),
                'error' => $errorBody,
                'request_body' => $body,
            ]);

            throw new SendcloudApiException('Sendcloud v3 request failed', $response->status(), $errorBody);
        }

        return $this->normalizeResponse($response->json());
    }

    private function buildToAddress(array $p): array
    {
        return array_filter([
            'name'           => $p['name'] ?? null,
            'company_name'   => $p['company_name'] ?: null,
            'address_line_1' => $p['address'] ?? null,
            'address_line_2' => $p['address_2'] ?? null,
            'city'           => $p['city'] ?? null,
            'postal_code'    => $p['postal_code'] ?? null,
            'country_code'   => $p['country'] ?? null,
            'phone_number'   => $p['telephone'] ?? null,
            'email'          => $p['email'] ?? null,
        ], fn($v) => $v !== null);
    }

    private function buildParcel(array $p): array
    {
        $items = $p['parcel_items'] ?? [];

        return array_filter([
            'weight' => [
                'value' => (string) $this->totalWeight($items),
                'unit' => 'kg',
            ],
            'parcel_items' => array_map(fn($item) => array_filter([
                'item_id'     => isset($item['item_id']) ? (string) $item['item_id'] : null,
                'description' => $item['description'] ?? null,
                'quantity'    => $item['quantity'] ?? 1,
                'weight' => [
                    'value' => (float) ($item['weight'] ?? 0.5),
                    'unit' => 'kg',
                ],
                'price' => [
                    'value' => (string) ($item['value'] ?? '0.00'),
                    'currency' => $p['total_order_value_currency'] ?? 'GBP',
                ],
                'sku'        => $item['sku'] ?? null,
                'product_id' => isset($item['product_id']) ? (string) $item['product_id'] : null,
                // 'properties' expects an object like {size: "XL"} — your current
                // {"Flavour": "Banana Ice"} shape actually matches this fine per schema
                'properties' => $item['properties'] ?? null,
            ], fn($v) => $v !== null), $items),
        ], fn($v) => $v !== null);
    }

    private function totalWeight(array $items): float
    {
        return array_reduce($items, fn($sum, $item) => $sum + (($item['weight'] ?? 0.1) * ($item['quantity'] ?? 1)), 0) ?: 0.1;
    }

    private function getSenderAddress(): array
    {
        if (!$this->sendcloud->default_sender_address_id) {
            throw new SendcloudApiException(
                'No sender address configured for this shop — please connect one in your Sendcloud panel, then re-save your Sendcloud settings.',
                422
            );
        }

        return ['sender_address_id' => $this->sendcloud->default_sender_address_id];
    }

    private function normalizeResponse(array $json): array
    {
        $parcel = $json['data']['parcels'][0] ?? [];
        return [
            'id' => $parcel['id'] ?? null,
            'order_number' => $json['data']['order_number'] ?? null,
            'tracking_number' => $parcel['tracking_number'] ?? null,
            'shipping_option_code' => $json['data']['ship_with']['properties']['shipping_option_code'] ?? null,  // ← must be this key name
            'status_code' => $parcel['status']['code'] ?? null,
            'status_message' => $parcel['status']['message'] ?? null,
        ];
    }
}
