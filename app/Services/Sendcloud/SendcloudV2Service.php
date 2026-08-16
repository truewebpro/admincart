<?php

namespace App\Services\Sendcloud;

use App\Exceptions\SendcloudApiException;
use App\Models\Sendcloud;
use Illuminate\Support\Facades\Http;

class SendcloudV2Service implements SendcloudServiceInterface
{
    public function __construct(private Sendcloud $sendcloud) {}

    public function createParcel(array $payload): array
    {
        $response = Http::withBasicAuth($this->sendcloud->public_key, $this->sendcloud->secret_key)
            ->post('https://panel.sendcloud.sc/api/v2/parcels', $payload);

        if (!$response->successful()) {
            throw new SendcloudApiException('Sendcloud v2 request failed', $response->status(), $response->json());
        }

        return $response->json()['parcel'];
    }
}
