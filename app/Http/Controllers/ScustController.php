<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerShop;
use App\Models\Scust;
use App\Models\ShopifyShop;
use App\Services\ShopifyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ScustController extends Controller
{
    /**
     * ALTERNATIVE to sync() above: uses Shopify's Bulk Operations API
     * instead of paginated REST. Better for large exports — runs
     * server-side on Shopify's infrastructure with zero rate-limit cost
     * to your app. Three-step flow: start, poll, then process.
     */
    public function startBulkSync(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $operationId = $service->startCustomersBulkExport();

        return response()->json(['success' => true, 'operation_id' => $operationId]);
    }

    /**
     * Poll this from your frontend every few seconds after startBulkSync().
     * When status is COMPLETED, call processBulkSync() next.
     */
    public function checkBulkSyncStatus(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        return response()->json($service->checkBulkOperationStatus());
    }

    /**
     * Once status is COMPLETED, call this with the result url from
     * checkBulkSyncStatus() to download + parse the JSONL and populate
     * scusts — same staging table, same downstream import() step as
     * the REST-based sync.
     */
    public function processBulkSync(Request $request, int $shopId)
    {
        $validated = $request->validate(['url' => ['required', 'url']]);

        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $count = 0;

        // Bulk export flattens addresses into separate JSONL lines linked
        // by __parentId when nested connections are involved — customers
        // with their addresses array (not a separate connection here,
        // since we queried it as a plain object list) come back as one
        // line per customer with addresses embedded directly.
        $service->streamBulkExportResults($validated['url'], function (array $customer) use ($shopId, &$count) {
            if (! isset($customer['id']) || ! str_contains($customer['id'], 'Customer')) {
                return; // skip any non-customer lines, just in case
            }

            $numericId = (string) basename($customer['id']); // gid://shopify/Customer/123 -> 123

            Scust::updateOrCreate(
                ['shop_id' => $shopId, 'thirdparty_id' => $numericId],
                [
                    'email'              => $customer['email'] ?? null,
                    'first_name'         => $customer['firstName'] ?? null,
                    'last_name'          => $customer['lastName'] ?? null,
                    'phone'              => $customer['phone'] ?? null,
                    'state'              => $customer['state'] ?? null,
                    'orders_count'       => $customer['ordersCount'] ?? 0,
                    'total_spent'        => $customer['amountSpent']['amount'] ?? 0,
                    'tags'               => is_array($customer['tags'] ?? null) ? implode(', ', $customer['tags']) : null,
                    'addresses'          => $customer['addresses'] ?? [],
                    'shopify_created_at' => $customer['createdAt'] ?? null,
                ]
            );

            $count++;
        });

        return response()->json(['success' => true, 'staged' => $count]);
    }

    /**
     * STEP 1: Pull customers from Shopify into staging. Cheap — one
     * upsert per customer, no address/tag/relation building yet. Run as
     * a queued job for large catalogs, same resumability pattern as before.
     */
    public function sync(int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyService($shopifyShop);

        $resumeFrom = Cache::get("customer_sync_since_id_{$shopId}", 0);

        $service->importCustomersSinceId(function (array $batch) use ($shopId) {
            foreach ($batch as $c) {
                Scust::updateOrCreate(
                    ['shop_id' => $shopId, 'thirdparty_id' => (string) $c['id']],
                    [
                        'email'               => $c['email'] ?? null,
                        'first_name'          => $c['first_name'] ?? null,
                        'last_name'           => $c['last_name'] ?? null,
                        'phone'               => $c['phone'] ?? null,
                        'state'               => $c['state'] ?? null,
                        'orders_count'        => $c['orders_count'] ?? 0,
                        'total_spent'         => $c['total_spent'] ?? 0,
                        'tags'                => $c['tags'] ?? null,
                        'addresses'           => $c['addresses'] ?? [],
                        'shopify_created_at'  => $c['created_at'] ?? null,
                    ]
                );
            }

            Cache::put("customer_sync_since_id_{$shopId}", end($batch)['id'], now()->addDay());
        }, 250, $resumeFrom);

        Cache::forget("customer_sync_since_id_{$shopId}");

        return response()->json(['success' => true, 'message' => 'Sync complete.']);
    }

    /**
     * STEP 2: Vuetify server-side table data — local only, no Shopify calls.
     */
    public function index(Request $request, int $shopId)
    {
        $query = Scust::where('shop_id', $shopId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('only_not_imported')) {
            $query->whereNull('customer_id');
        }
        $allowedSorts = ['email', 'first_name', 'last_name', 'orders_count', 'total_spent', 'created_at'];
        $sortBy = in_array($request->sort_by, $allowedSorts) ? $request->sort_by : 'id';
        $sortOrder = $request->sort_order === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);
        $perPage = (int) $request->input('per_page', 50);
//        $page = $query->orderBy($request->input('sort', 'email'))->paginate($perPage);
        $page = $query->paginate($perPage);

        $stotal = Scust::where('shop_id', $shopId)->count();
        return response()->json([
            'items'     => $page,
            'stotal'     => $stotal,
        ]);
    }

    /**
     * STEP 3: Import selected staging rows into customers /
     * customer_addresses / customer_shops. Accepts specific IDs or
     * import_all — same "1, 50, or selected" pattern as products.
     */
    public function import(Request $request, int $shopId)
    {
        $validated = $request->validate([
            'scust_ids'   => ['nullable', 'array'],
            'scust_ids.*' => ['integer', 'exists:scusts,id'],
            'import_all'  => ['nullable', 'boolean'],
        ]);

        $query = Scust::where('shop_id', $shopId)->whereNull('customer_id');

        if (! ($validated['import_all'] ?? false)) {
            $query->whereIn('id', $validated['scust_ids'] ?? []);
        }

        $imported = 0;

        $query->chunk(50, function ($scusts) use (&$imported, $shopId) {
            DB::transaction(function () use ($scusts, $shopId, &$imported) {
                foreach ($scusts as $scust) {
                    if (! $scust->email) {
                        continue; // customers table requires a unique email
                    }

                    $customer = Customer::where('email', $scust->email)->first();

                    if (! $customer) {
                        $customer = Customer::create([
                            'fname'    => $scust->first_name ?: $this->nameFromEmail($scust->email),
                            'lname'    => $scust->last_name,
                            'email'    => $scust->email,
                            'password' => Hash::make(Str::random(32)),
                            'phone'    => $scust->phone,
                            'status'   => $scust->state === 'disabled' ? 'active' : 'active',
                        ]);
                    } else {
                        $customer->update([
                            'fname' => $scust->first_name ?? $customer->fname,
                            'lname' => $scust->last_name ?? $customer->lname,
                            'phone' => $scust->phone ?? $customer->phone,
                        ]);
                    }

                    // --- Addresses: upsert by thirdparty_id ---
                    $seenAddressIds = [];

                    foreach ($scust->addresses ?? [] as $address) {
                        $shopifyAddressId = isset($address['id']) ? (string) $address['id'] : null;
                        $seenAddressIds[] = $shopifyAddressId;

                        CustomerAddress::updateOrCreate(
                            ['customer_id' => $customer->customer_id, 'thirdparty_id' => $shopifyAddressId],
                            [
                                'address_title' => 'Shipping',
                                'fname'         => $address['first_name'] ?? $customer->fname,
                                'lname'         => $address['last_name'] ?? $customer->lname,
                                'address_line1' => $address['address1'] ?? '',
                                'address_line2' => $address['address2'] ?? null,
                                'city'          => $address['city'] ?? '',
                                'postcode'      => $address['zip'] ?? '',
                                'country'       => $address['country_code'] ?? 'GB',
                                'phone'         => $address['phone'] ?? null,
                                'is_default'    => (bool) ($address['default'] ?? false),
                            ]
                        );
                    }

                    CustomerAddress::where('customer_id', $customer->customer_id)
                        ->whereNotNull('thirdparty_id')
                        ->whereNotIn('thirdparty_id', $seenAddressIds ?: ['__none__'])
                        ->delete();

                    // --- Shop link ---
                    $tags = ! empty($scust->tags)
                        ? array_map('trim', explode(',', $scust->tags))
                        : null;

                    CustomerShop::updateOrCreate(
                        ['customer_id' => $customer->customer_id, 'shop_id' => $shopId],
                        [
                            'thirdparty_id' => $scust->thirdparty_id,
                            'thirdparty_orders_count'  => $scust->orders_count ?? 0,
                            'thirdparty_spent'         => $scust->total_spent ?? 0,
                            'ctags'         => $tags,
                            'status'        => 'active',
                            'registered_at' => $scust->shopify_created_at ?? now(),
                        ]
                    );

                    $scust->update([
                        'customer_id'  => $customer->customer_id,
                        'imported_at'  => now(),
                    ]);

                    $imported++;
                }
            });
        });

        return response()->json(['success' => true, 'imported' => $imported]);
    }

    protected function nameFromEmail(string $email): string
    {
        $localPart = explode('@', $email)[0] ?? 'Customer';

        // Emails often use dots/underscores/hyphens as word separators —
        // turn "john.smith" or "john_smith" into "John Smith" rather than
        // leaving it as an unbroken lowercase blob.
        $cleaned = str_replace(['.', '_', '-'], ' ', $localPart);

        return ucwords(trim($cleaned)) ?: 'Customer';
    }

}
