<?php

namespace App\Http\Controllers;

use App\Models\ActiveVisitor;
use App\Models\PageView;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
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

        PageView::create([
            'shop_id' => $shopId,
            'session_id' => $validated['session_id'],
            'customer_id' => $customerId,
            'path' => $validated['path'],
            'referrer' => $validated['referrer'] ?? null,
            'device_type' => $validated['device_type'] ?? null,
            'browser' => $validated['browser'] ?? null,
        ]);

        ActiveVisitor::updateOrCreate(
            ['shop_id' => $shopId, 'session_id' => $validated['session_id']],
            [
                'customer_id' => $customerId,
                'current_path' => $validated['path'],
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Lightweight — called every ~25-30s while a tab stays open on the
     * same page, WITHOUT logging a new page_views row each time (that
     * would massively inflate pageview counts). Just keeps last_seen_at
     * fresh so the live count stays accurate for someone reading a
     * single product page for a while.
     */
    public function heartbeat(Request $request)
    {
        $shopId = $request->attributes->get('shop_id');

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:100'],
            'path' => ['nullable', 'string', 'max:500'],
        ]);

        $customerId = $request->attributes->get('customer_id');

        ActiveVisitor::updateOrCreate(
            ['shop_id' => $shopId, 'session_id' => $validated['session_id']],
            [
                'customer_id' => $customerId,
                'current_path' => $validated['path'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }
}
