<?php

namespace App\Services;

use GeoIp2\Database\Reader;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    protected ?Reader $reader = null;

    public function __construct()
    {
        $dbPath = storage_path('app/geoip/GeoLite2-City.mmdb');

        if (file_exists($dbPath)) {
            $this->reader = new Reader($dbPath);
        }
    }

    /**
     * Resolves an IP to location data. Returns nulls for everything if
     * the DB isn't installed yet, or the IP is private/local/unresolvable
     * (very common in local dev — 127.0.0.1 / 192.168.x.x always miss).
     */
    public function lookup(?string $ip): array
    {
        $empty = [
            'country' => null,
            'region' => null,
            'city' => null,
            'latitude' => null,
            'longitude' => null,
        ];

        if (! $ip || ! $this->reader || $this->isPrivateIp($ip)) {
            return $empty;
        }

        try {
            $record = $this->reader->city($ip);

            return [
                'country' => $record->country->name,
                'region' => $record->mostSpecificSubdivision->name,
                'city' => $record->city->name,
                'latitude' => $record->location->latitude,
                'longitude' => $record->location->longitude,
            ];
        } catch (\Exception $e) {
            // AddressNotFoundException is expected/common (IP not in DB
            // yet, or genuinely unassigned) — log quietly, don't throw,
            // since a failed geo lookup should never break tracking.
            Log::debug("GeoIP lookup failed for {$ip}: " . $e->getMessage());
            return $empty;
        }
    }

    protected function isPrivateIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
