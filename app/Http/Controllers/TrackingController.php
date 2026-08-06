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
     * Resolves the REAL visitor IP, not the connecting server's IP.
     * Since requests arrive via Next.js's server (Vercel), $request->ip()
     * alone would return Vercel's IP. The Next.js BFF route explicitly
     * forwards the true client IP via X-Forwarded-For — trust that
     * header first, fall back to $request->ip() only if it's missing
     * (e.g. someone hits this endpoint directly, bypassing Next.js).
     */
    protected function resolveVisitorIp(Request $request): ?string
    {
        $forwarded = $request->header('X-Forwarded-For');

        if ($forwarded) {
            return trim(explode(',', $forwarded)[0]);
        }

        return $request->ip();
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
        $geo = $this->geoIp->lookup($this->resolveVisitorIp($request));
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
        $geo = $this->geoIp->lookup($this->resolveVisitorIp($request));
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
