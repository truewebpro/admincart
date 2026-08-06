<?php

namespace App\Http\Controllers;

use App\Models\ActiveVisitor;
use App\Models\PageView;
use App\Services\GeoIpService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(protected GeoIpService $geoIp)
    {
    }

    /**
     * Resolves geo data with two sources, in priority order:
     *
     * 1. Pre-resolved geo sent directly from Next.js (Vercel's own edge
     *    network already geolocates every request before our function
     *    even runs — this is more reliable than IP-forwarding, since
     *    intermediate proxies/hosts commonly strip or overwrite a
     *    client-supplied X-Forwarded-For header for security reasons,
     *    which is exactly what was happening: Laravel was resolving
     *    Vercel's own execution region instead of the real visitor).
     * 2. Fallback: local MaxMind lookup via $request->ip(), for
     *    non-Vercel traffic (local dev, direct API testing, or if this
     *    ever runs behind something other than Vercel).
     */
    protected function resolveGeo(Request $request): array
    {
        $geo = $request->input('geo');

        if (is_array($geo) && ! empty(array_filter($geo))) {
            return [
                'country' => $geo['country'] ?? null,
                'region' => $geo['region'] ?? null,
                'city' => $geo['city'] ?? null,
                'latitude' => $geo['latitude'] ?? null,
                'longitude' => $geo['longitude'] ?? null,
            ];
        }

        return $this->geoIp->lookup($request->ip());
    }

    /**
     * Called on every route change in Next.js. Logs a permanent record
     * AND upserts the live-visitor heartbeat in one call, since a
     * pageview always implies the visitor is currently active.
     */
    public function pageview(Request $request)
    {
        $shopId = $request->attributes->get('shop_id');

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'path' => ['required', 'string', 'max:500'],
            'referrer' => ['nullable', 'string', 'max:500'],
            'device_type' => ['nullable', 'string', 'max:50'],
            'browser' => ['nullable', 'string', 'max:100'],
        ]);

        $customerId = $request->attributes->get('customer_id'); // set this if you resolve logged-in customers on public routes; null otherwise
        $geo = $this->resolveGeo($request);
//        $geo = $this->geoIp->lookup('81.2.69.142');

        PageView::create([
            'shop_id' => $shopId,
            'session_id' => $validated['session_id'],
            'customer_id' => $customerId,
            'path' => $validated['path'],
            'referrer' => $validated['referrer'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'browser' => $validated['browser'] ?? null,
            'country' => $geo['country'],
            'region' => $geo['region'],
            'city' => $geo['city'],
            'latitude' => $geo['latitude'],
            'longitude' => $geo['longitude'],
        ]);

        ActiveVisitor::updateOrCreate(
            ['shop_id' => $shopId, 'session_id' => $validated['session_id']],
            [
                'customer_id' => $customerId,
                'current_path' => $validated['path'],
                'last_seen_at' => now(),
                'country' => $geo['country'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
            ]
        );

        return response()->json(['success' => true,'geo'=>$geo]);
    }

    /**
     * Lightweight — called every ~25-30s while a tab stays open on the
     * same page. Re-resolves geo too (cheap local DB lookup, no API
     * call), so if a session's IP somehow changes mid-visit the
     * location stays current.
     */
    public function heartbeat(Request $request)
    {
        $shopId = $request->attributes->get('shop_id');

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:500'],
        ]);

        $customerId = $request->attributes->get('customer_id');
        $geo = $this->resolveGeo($request);
//        $geo = $this->geoIp->lookup('81.2.69.142');

        ActiveVisitor::updateOrCreate(
            ['shop_id' => $shopId, 'session_id' => $validated['session_id']],
            [
                'customer_id' => $customerId,
                'current_path' => $validated['path'] ?? null,
                'last_seen_at' => now(),
                'country' => $geo['country'],
                'region' => $geo['region'],
                'city' => $geo['city'],
                'latitude' => $geo['latitude'],
                'longitude' => $geo['longitude'],
            ]
        );

        return response()->json(['success' => true,'geo'=>$geo]);
    }
}
