<?php
namespace App\Services\Sendcloud;

use App\Exceptions\SendcloudApiException;
use App\Models\Sendcloud;
use App\Models\SendcloudShippingOption;
use Illuminate\Support\Facades\Http;

class SendcloudShippingOptionSyncer
{
    public function sync(Sendcloud $sendcloud): void
    {
        $response = Http::withBasicAuth($sendcloud->public_key, $sendcloud->secret_key)
            ->post('https://panel.sendcloud.sc/api/v3/shipping-options');

        if (!$response->successful()) {
            throw new SendcloudApiException(
                message: 'Sendcloud API request failed',
                statusCode: $response->status(),
                errorBody: $response->json(),
            );
        }

        $seenCodes = [];

        foreach ($response->json('data', []) as $option) {
            $seenCodes[] = $option['code']; // confirm actual key name against the response

            SendcloudShippingOption::updateOrCreate(
                [
                    'sendcloud_id' => $sendcloud->id,
                    'shipping_option_code' => $option['code'],
                ],
                [
                    'carrier' => $option['carrier']['name'] ?? null,
                    'name' => $option['product']['name'] ?? $option['code'],
                    'is_active' => true,
                ]
            );
        }

        // deactivate options Sendcloud no longer returns (carrier removed, contract changed, etc.)
        SendcloudShippingOption::where('sendcloud_id', $sendcloud->id)
            ->whereNotIn('shipping_option_code', $seenCodes)
            ->update(['is_active' => false]);
    }
}
