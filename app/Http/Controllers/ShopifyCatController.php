<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingShopifyScopeException;
use App\Jobs\SyncCollectionSeoJob;
use App\Models\Brand;
use App\Models\Cat;
use App\Models\ProductType;
use App\Models\Rule;
use App\Models\ShopifyShop;
use App\Services\ImageService;
use App\Services\ShopifyCollectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ShopifyCatController extends Controller
{
    /**
     * Combined live view of BOTH custom and smart collections, each
     * tagged with its type so a single table can distinguish them
     * (e.g. via a chip column) without needing separate tabs — unlike
     * blogs, there are only ever exactly two collection types, not an
     * arbitrary number, so one flat list is simpler here.
     */
    public function liveCollections(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyCollectionService($shopifyShop);

        try {
            $custom = collect($service->getCustomCollections())->map(fn ($c) => array_merge($c, ['cat_type' => 'manual']));
            $smart = collect($service->getSmartCollections())->map(fn ($c) => array_merge($c, ['cat_type' => 'smart']));

            $combined = $custom->concat($smart)->values();

            // One batched GraphQL call (or a few, if >100 collections)
            // instead of one REST call per row.
            $ids = $combined->pluck('id')->map(fn ($id) => (int) $id)->all();
            $counts = $ids ? $service->getCollectionsProductCounts($ids) : [];

            $combined = $combined->map(function ($c) use ($counts) {
                $c['products_count'] = $counts[(int) $c['id']] ?? null;
                return $c;
            });
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json([
            'success' => true,
            'collections' => $combined,
        ]);
    }


    /**
     * Single, explicit create — no bulk. Re-fetches the specific
     * collection from Shopify by ID + type (authoritative), creates a
     * real Cat row, and for smart collections, ALSO recreates the
     * rules (delete-then-recreate, same pattern established for the
     * original bulk import — a smart collection without its rules
     * isn't a working collection, so this isn't optional).
     */
    public function create(Request $request, int $shopId, string $type, string $collectionId)
    {
        if (! in_array($type, ['manual', 'smart'], true)) {
            return response()->json(['success' => false, 'message' => 'Invalid collection type.'], 422);
        }

        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyCollectionService($shopifyShop);

        try {
            $collection = $service->getCollectionById($type, $collectionId);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        if (empty($collection['id']) || empty($collection['handle'])) {
            return response()->json(['success' => false, 'message' => 'Invalid collection data.'], 422);
        }

        $existing = Cat::where('shop_id', $shopId)
            ->where('thirdparty_id', (string) $collection['id'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This collection has already been created.',
                'cat_id' => $existing->cat_id,
            ], 422);
        }

        $catImage = null;
        if (! empty($collection['image']['src'])) {
            try {
                $catImage = ImageService::storeFromUrl($collection['image']['src'], 'category', 600, 600);
            } catch (\Throwable $e) {
                $catImage = null; // don't block collection creation if the image fails
            }
        }

        $cat = Cat::create([
            'shop_id'            => $shopId,
            'thirdparty_id'      => (string) $collection['id'],
            'thirdparty_handle'  => $collection['handle'],
            'cat_name'           => $collection['title'] ?? 'Untitled',
            'cat_slug'           => $collection['handle'],
            'cat_desc'           => $collection['body_html'] ?? null,
            'short_desc'         => '',
            'cat_status'         => 'Active',
            'cat_image'          => $catImage,
            'cat_type'           => $type,
            'cat_rule'           => 'and',
            'sort_order'         => 'title_asc',
            'meta_title'         => $collection['title'] ?? null,
            'meta_desc'          => $collection['title'] ?? null,
        ]);

        // Smart collections carry rules — recreate them now, same
        // vendor/type-resolution + delete-then-recreate pattern as the
        // original bulk importSmartCollections() flow.
        if ($type === 'smart' && ! empty($collection['rules'])) {
            $this->recreateRules($cat, $collection['rules'], $shopId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Collection created.',
            'cat_id' => $cat->cat_id,
        ]);
    }

    /**
     * One-time backfill for collections imported BEFORE thirdparty_id/
     * thirdparty_handle existed — same update-only, match-by-slug,
     * safe-to-re-run pattern as the blogs backfill.
     */
    /**
     * Syncs SEO title/description for already-created collections, from
     * Shopify's title_tag/description_tag metafields. Only updates rows
     * with a thirdparty_id — collections created manually in your own
     * admin are correctly left alone.
     */
    public function syncSeo(int $shopId)
    {
        $lockKey = "collection_seo_sync_running_{$shopId}"; // our own explicit flag — not relying on Laravel's internal ShouldBeUnique lock storage, which is an implementation detail

        if (Cache::has($lockKey)) {
            return response()->json([
                'success' => false,
                'message' => 'A SEO sync is already running for this shop — please wait for it to finish.',
            ], 409);
        }

        // Set BEFORE dispatch so the message is accurate whether the job is
        // still queued or actively processing — the job itself clears this
        // in a finally block when it completes (success or failure).
        Cache::put($lockKey, true, now()->addMinutes(10));

        SyncCollectionSeoJob::dispatch($shopId);

        return response()->json([
            'success' => true,
            'message' => 'SEO sync started in the background. This may take a minute for large catalogs.',
        ]);
    }


    public function backfillThirdpartyIds(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyCollectionService($shopifyShop);

        try {
            $custom = collect($service->getCustomCollections())->map(fn ($c) => array_merge($c, ['cat_type' => 'manual']));
            $smart = collect($service->getSmartCollections())->map(fn ($c) => array_merge($c, ['cat_type' => 'smart']));
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $matched = 0;
        $checked = 0;

        foreach ($custom->concat($smart) as $collection) {
            $checked++;

            $existing = Cat::where('shop_id', $shopId)
                ->where('cat_slug', $collection['handle'])
                ->where('cat_type', $collection['cat_type'])
                ->whereNull('thirdparty_id')
                ->first();

            if ($existing) {
                $existing->update([
                    'thirdparty_id'     => (string) $collection['id'],
                    'thirdparty_handle' => $collection['handle'],
                ]);
                $matched++;
            }
        }

        return response()->json(['success' => true, 'checked' => $checked, 'matched' => $matched]);
    }

    /**
     * Delete-then-recreate — same reasoning established earlier: rules
     * have no stable per-rule ID from Shopify to match against
     * individually, and duplicates/near-duplicates on the same column
     * are possible, so wholesale replace is the only correct approach.
     */
    protected function recreateRules($cat, array $shopifyRules, int $shopId): void
    {
        Rule::where('shop_id', $shopId)->where('cat_id', $cat->cat_id)->delete();

        foreach ($shopifyRules as $rule) {
            $mapped = $this->mapRule($rule, $shopId);
            Rule::create([
                'shop_id'   => $shopId,
                'cat_id'    => $cat->cat_id,
                'column'    => $mapped['column'],
                'relation'  => $mapped['relation'],
                'condition' => $mapped['condition'],
            ]);
        }
    }

    protected function mapRule(array $rule, int $shopId): array
    {
        switch ($rule['column']) {
            case 'vendor':
                return [
                    'column'    => 'vendor',
                    'relation'  => $rule['relation'],
                    'condition' => $this->getBrandId($rule['condition'], $shopId),
                ];
            case 'type':
                return [
                    'column'    => 'type',
                    'relation'  => $rule['relation'],
                    'condition' => $this->getTypeId($rule['condition'], $shopId),
                ];
            case 'tag':
                return [
                    'column'    => 'tag',
                    'relation'  => $rule['relation'],
                    'condition' => $rule['condition'],
                ];
            default:
                return [
                    'column'    => $rule['column'],
                    'relation'  => $rule['relation'],
                    'condition' => $rule['condition'],
                ];
        }
    }

    protected function getBrandId(?string $brandName, int $shopId)
    {
        if (! $brandName) return null;

        return Brand::firstOrCreate(
            ['brand_slug' => Str::slug(strtolower(trim($brandName)), '-'), 'shop_id' => $shopId],
            ['brand_name' => trim($brandName)]
        )->brand_id;
    }

    protected function getTypeId(?string $typeName, int $shopId)
    {
        if (! $typeName) return null;

        return ProductType::firstOrCreate([
            'product_type_name' => trim($typeName),
            'shop_id' => $shopId,
        ])->product_type_id;
    }
}
