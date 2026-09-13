<?php

namespace App\Http\Controllers;

use App\Models\JobRun;

class JobRunController extends Controller
{
    /**
     * All five job types (collection/product/blog SEO sync, variant
     * cost sync, variant backfill) share this one table — this is the
     * single endpoint that gives your dashboard everything it needs
     * about sync status for a shop, rather than each resource type
     * needing its own status-checking endpoint.
     */
    protected const JOB_TYPES = [
        'collection_seo_sync',
        'product_seo_sync',
        'blog_seo_sync',
        'variant_cost_sync',
        'variant_backfill',
    ];

    public function index(int $shopId)
    {
        // One card per job type, showing its MOST RECENT run only —
        // exactly what a "Collections: last synced 2 hours ago, 45
        // updated" status widget needs, without the frontend having to
        // filter/group a flat list itself.
        $latestByType = [];

        foreach (self::JOB_TYPES as $type) {
            $latestByType[$type] = JobRun::where('shop_id', $shopId)
                ->where('job_type', $type)
                ->orderByDesc('created_at')
                ->first(); // null if this job type has never run for this shop — a real, valid state, not an error
        }

        // Separate flat history — useful for a "recent activity" log
        // view, distinct from the per-type status cards above.
        $recent = JobRun::where('shop_id', $shopId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'latest_by_type' => $latestByType,
            'recent' => $recent,
        ]);
    }
}
