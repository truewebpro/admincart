<?php

namespace App\Http\Controllers;

use App\Enums\DiscountType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingShippingAddressException;
use App\Http\Requests\DraftOrder\AddDraftOrderItemRequest;
use App\Http\Requests\DraftOrder\ApplyDraftOrderDiscountRequest;
use App\Http\Requests\DraftOrder\ConvertDraftOrderRequest;
use App\Http\Requests\DraftOrder\CreateCustomerAddressRequest;
use App\Http\Requests\DraftOrder\CreateDraftOrderCustomerRequest;
use App\Http\Requests\DraftOrder\RecordDraftOrderPaymentRequest;
use App\Http\Requests\DraftOrder\SetDraftOrderCustomerRequest;
use App\Http\Requests\DraftOrder\SetDraftOrderShippingAddressRequest;
use App\Http\Requests\DraftOrder\StoreDraftOrderRequest;
use App\Http\Requests\DraftOrder\SyncDraftOrderTagsRequest;
use App\Http\Requests\DraftOrder\UpdateDraftOrderItemRequest;
use App\Http\Requests\DraftOrder\UpdateDraftOrderNotesRequest;
use App\Http\Requests\DraftOrder\UpdateDraftOrderShippingRequest;
use App\Http\Resources\DraftOrderItemResource;
use App\Http\Resources\DraftOrderListResource;
use App\Http\Resources\DraftOrderPaymentResource;
use App\Http\Resources\CustomerAddressResource;
use App\Http\Resources\CustomerSearchResource;
use App\Http\Resources\DraftOrderResource;
use App\Http\Resources\ShipMethodResource;
use App\Http\Resources\VariantSearchResource;
use App\Models\Ctag;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerShop;
use App\Models\DraftOrder;
use App\Models\DraftOrderItem;
use App\Models\ShipMethod;
use App\Models\Shop;
use App\Models\Variant;
use App\Services\DraftOrderConversionService;
use App\Services\DraftOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Single controller for all draft order actions (create, items, customer,
 * discount, shipping, notes, tags, payments, convert, duplicate) — per
 * your instruction to consolidate rather than split into many small
 * controllers. Document generation (PDF/invoice/print) is deliberately
 * NOT here — that's a distinct concern for a future DraftOrderDocumentController.
 *
 * No permission system is in use, so authorization here is exactly two
 * things: (1) route middleware requires an authenticated admin session
 * (enforced outside this class, in web.php), and (2) shop isolation,
 * enforced explicitly by resolveDraftOrder()/resolveItem() below on
 * every single action. There is no global scope doing this automatically
 * — it was deliberately removed — so every method that touches a
 * DraftOrder MUST go through one of those two resolvers, never a raw
 * find()/route-model-binding lookup.
 */
class DraftOrderController extends Controller
{
    public function __construct(
        private readonly DraftOrderService $draftOrderService,
        private readonly DraftOrderConversionService $conversionService,
    ) {
    }

    // ------------------------------------------------------------------
    // Shop isolation helpers — every action uses one of these instead of
    // trusting a route-bound model directly.
    // ------------------------------------------------------------------

    private function currentShopId(): int
    {
        $shopId = session('shop_id');

        abort_unless($shopId, 403, 'No shop context.');

        return (int) $shopId;
    }

    /**
     * Resolves a DraftOrder by id, scoped to the current session shop.
     * 404s (not 403) if it belongs to a different shop, so as not to leak
     * whether the id exists at all in another shop.
     */
    private function resolveDraftOrder(int $id, array $with = []): DraftOrder
    {
        /** @var DraftOrder $draftOrder */
        $draftOrder = DraftOrder::where('id', $id)
            ->where('shop_id', $this->currentShopId())
            ->with($with)
            ->firstOrFail();

        return $draftOrder;
    }

    private function resolveItem(DraftOrder $draftOrder, int $itemId): DraftOrderItem
    {
        /** @var DraftOrderItem $item */
        $item = $draftOrder->items()->where('id', $itemId)->firstOrFail();

        return $item;
    }

    /**
     * Re-fetches a draft order with the full standard relation set and
     * wraps it in a resource. Deliberately uses fresh() (always re-queries
     * and eager-loads whatever you ask for), NOT refresh() (only reloads
     * relations that were already loaded on that instance) — several
     * actions were calling refresh() on a DraftOrder that never had
     * 'items' loaded in the first place, so the relation stayed empty and
     * whenLoaded('items') silently dropped it from the JSON response
     * entirely, rather than sending an empty array.
     */
    private function freshDraftOrderResource(DraftOrder $draftOrder): DraftOrderResource
    {
        return new DraftOrderResource(
            $draftOrder->fresh(['items.variant', 'customer', 'payments', 'tags'])
        );
    }

    // ------------------------------------------------------------------
    // Listing / CRUD
    // ------------------------------------------------------------------

    public function index(Request $request): JsonResponse
    {
        $query = DraftOrder::where('shop_id', $this->currentShopId())
            ->withCount('items')
            ->with('customer');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('draft_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('fname', 'like', "%{$search}%")
                            ->orWhere('lname', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->get('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($createdBy = $request->get('created_by')) {
            $query->where('created_by', $createdBy);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($ctagId = $request->get('ctag_id')) {
            $query->whereHas('tags', fn ($q) => $q->where('ctags.id', $ctagId));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'updated_at', 'total', 'draft_number', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $draftOrders = $query->paginate($perPage);

        return DraftOrderListResource::collection($draftOrders)->response();
    }

    public function store(StoreDraftOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! empty($data['customer_id'])) {
            $this->assertCustomerBelongsToShop((int) $data['customer_id']);
        }

        $draftOrder = $this->draftOrderService->createDraft(
            $this->currentShopId(),
            auth()->id(),
            $data
        );

        return (new DraftOrderResource($draftOrder->load('customer', 'items', 'payments', 'tags')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id, [
            'customer', 'items.variant', 'payments', 'tags',
        ]);

        return (new DraftOrderResource($draftOrder))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $draftOrder->delete(); // soft delete

        return response()->json(['message' => 'Draft order deleted.']);
    }

    public function duplicate(int $id): JsonResponse
    {
        $source = $this->resolveDraftOrder($id, ['items', 'tags']);

        $copy = $this->draftOrderService->duplicateDraft($source, auth()->id());

        return (new DraftOrderResource($copy->load('customer', 'items', 'payments', 'tags')))
            ->response()
            ->setStatusCode(201);
    }

    // ------------------------------------------------------------------
    // Items
    // ------------------------------------------------------------------

    public function addItem(int $id, AddDraftOrderItemRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $data = $request->validated();

        $variant = Variant::where('variant_id', $data['variant_id'])
            ->where('shop_id', $this->currentShopId())
            ->firstOrFail();

        $discount = isset($data['discount_type'])
            ? ['type' => DiscountType::from($data['discount_type']), 'value' => $data['discount_value'] ?? 0]
            : null;

        $result = $this->draftOrderService->addProductItem(
            $draftOrder,
            $variant,
            $data['quantity'],
            $data['unit_price'] ?? null,
            $discount
        );

        return response()->json([
            'item' => new DraftOrderItemResource($result['item']->load('variant')),
            'stock_warning' => $result['stock_warning'],
            'draft_order' => $this->freshDraftOrderResource($draftOrder),
        ], 201);
    }

    public function updateItem(int $id, int $itemId, UpdateDraftOrderItemRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $item = $this->resolveItem($draftOrder, $itemId);

        $updated = $this->draftOrderService->updateItem($item, $request->validated());

        return response()->json([
            'item' => new DraftOrderItemResource($updated->load('variant')),
            'draft_order' => $this->freshDraftOrderResource($draftOrder),
        ]);
    }

    public function removeItem(int $id, int $itemId): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $item = $this->resolveItem($draftOrder, $itemId);

        $draftOrder = $this->draftOrderService->removeItem($item);

        return response()->json([
            'draft_order' => $this->freshDraftOrderResource($draftOrder),
        ]);
    }

    // ------------------------------------------------------------------
    // Customer
    // ------------------------------------------------------------------

    public function setCustomer(int $id, SetDraftOrderCustomerRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $customerId = (int) $request->validated('customer_id');

        $this->assertCustomerBelongsToShop($customerId);

        $draftOrder = $this->draftOrderService->setCustomer($draftOrder, $customerId);

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    public function removeCustomer(int $id): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $draftOrder = $this->draftOrderService->removeCustomer($draftOrder);

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    /**
     * Confirms the customer has a customer_shops row for the current
     * shop (i.e. they're actually tradeable by this shop) before letting
     * them be attached to a draft order — mirrors Customer::scopeForShop().
     */
    private function assertCustomerBelongsToShop(int $customerId): void
    {
        $exists = Customer::where('customer_id', $customerId)
            ->forShop(Shop::where('shop_id', $this->currentShopId())->firstOrFail())
            ->exists();

        abort_unless($exists, 404, 'Customer not found for this shop.');
    }

    // ------------------------------------------------------------------
    // Customer addresses
    // ------------------------------------------------------------------

    public function listCustomerAddresses(int $customerId): JsonResponse
    {
        $this->assertCustomerBelongsToShop($customerId);

        $addresses = CustomerAddress::where('customer_id', $customerId)
            ->orderByDesc('is_default')
            ->get();

        return CustomerAddressResource::collection($addresses)->response();
    }

    public function createCustomerAddress(int $customerId, CreateCustomerAddressRequest $request): JsonResponse
    {
        $this->assertCustomerBelongsToShop($customerId);
        $data = $request->validated();

        // First address for this customer becomes the default automatically,
        // regardless of what was submitted — a customer should never end up
        // with zero default addresses just because is_default wasn't ticked.
        $hasExisting = CustomerAddress::where('customer_id', $customerId)->exists();
        $isDefault = ! $hasExisting || (bool) ($data['is_default'] ?? false);

        if ($isDefault) {
            CustomerAddress::where('customer_id', $customerId)->update(['is_default' => false]);
        }

        $address = CustomerAddress::create([
            'customer_id' => $customerId,
            'address_title' => 'Shipping',
            'fname' => $data['fname'],
            'lname' => $data['lname'] ?? '',
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'postcode' => $data['postcode'],
            'country' => $data['country'] ?? 'GB',
            'phone' => $data['phone'] ?? null,
            'is_default' => $isDefault,
        ]);

        return (new CustomerAddressResource($address))->response()->setStatusCode(201);
    }

    /**
     * Attaches a real CustomerAddress to the draft order — required
     * before convert() will allow turning it into a real Order, since
     * orders.address_id needs a genuine FK, not just a JSON snapshot.
     */
    public function setShippingAddress(int $id, SetDraftOrderShippingAddressRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $addressId = (int) $request->validated('address_id');

        // The address must belong to THIS draft order's customer — not
        // just any address in the system.
        abort_unless($draftOrder->customer_id, 422, 'Attach a customer before setting a shipping address.');

        $address = CustomerAddress::where('address_id', $addressId)
            ->where('customer_id', $draftOrder->customer_id)
            ->firstOrFail();

        $draftOrder = $this->draftOrderService->setShippingAddress($draftOrder, $address);

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    /**
     * Searches customers already known to the current shop (i.e. with a
     * customer_shops row here) by name, email, phone, postcode, or
     * business name — per your original spec's search fields. Business
     * name lives on CustomerBusinessProfile (shop-scoped via
     * customer_shops), postcode on CustomerAddress — neither is a column
     * on customers itself, hence the joins via whereHas below.
     *
     * Deliberately does NOT search customers outside this shop — a
     * customer registered only with a different shop won't appear here,
     * even though they exist globally in the customers table. If you
     * want cross-shop lookup with an explicit "link to this shop" action
     * for admins, that's a different, bigger endpoint — this one assumes
     * search stays shop-local and createCustomer() handles the
     * already-exists-elsewhere case transparently instead.
     */
    public function searchCustomers(Request $request): JsonResponse
    {
        $shop = Shop::where('shop_id', $this->currentShopId())->firstOrFail();
        $search = $request->get('q');

        $query = Customer::forShop($shop)
            ->with([
                'addresses',
                'shops' => fn ($q) => $q->where('shop_id', $shop->shop_id)->with('businessProfile'),
            ]);

        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms, $shop) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term, $shop) {
                        $subQ->where('fname', 'LIKE', "%{$term}%")
                            ->orWhere('lname', 'LIKE', "%{$term}%")
                            ->orWhere('email', 'LIKE', "%{$term}%")
                            ->orWhere('phone', 'LIKE', "%{$term}%")
                            ->orWhereHas('addresses', function ($aq) use ($term) {
                                $aq->where('postcode', 'LIKE', "%{$term}%");
                            })
                            ->orWhereHas('shops', function ($sq) use ($term, $shop) {
                                $sq->where('shop_id', $shop->shop_id)
                                    ->whereHas('businessProfile', function ($bq) use ($term) {
                                        $bq->where('business_name', 'LIKE', "%{$term}%");
                                    });
                            });
                    });
                }
            });
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $customers = $query->paginate($perPage);

        return CustomerSearchResource::collection($customers)->response();
    }

    /**
     * Quick-create a customer from the draft order flow. If the email
     * already exists globally (this person is a customer of a different
     * shop), links the EXISTING customer to this shop instead of erroring
     * on the unique email constraint or creating a duplicate — see class
     * docblock note on searchCustomers() for the reasoning.
     *
     * Sets a random, unusable password on brand-new customers — this is
     * an admin-created record, not a storefront self-registration; the
     * customer can go through a normal password-reset flow later if they
     * ever need to log in themselves. customers.password is NOT NULL,
     * so some value has to be set regardless.
     */
    public function createCustomer(CreateDraftOrderCustomerRequest $request): JsonResponse
    {
        $data = $request->validated();
        $shopId = $this->currentShopId();

        $customer = DB::transaction(function () use ($data, $shopId) {
            $customer = Customer::where('email', $data['email'])->first();

            if (! $customer) {
                $customer = Customer::create([
                    'fname' => $data['fname'],
                    'lname' => $data['lname'] ?? null,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'status' => 'active',
                    'password' => Hash::make(Str::random(40)),
                ]);
            }

            // Link (or re-activate the link) to this shop — covers both
            // the brand-new customer and the "already exists elsewhere"
            // case identically.
            CustomerShop::updateOrCreate(
                ['customer_id' => $customer->customer_id, 'shop_id' => $shopId],
                ['status' => 'active', 'registered_at' => now()]
            );

            if (! empty($data['address_line1'])) {
                CustomerAddress::create([
                    'customer_id' => $customer->customer_id,
                    'address_title' => 'Shipping',
                    'fname' => $data['fname'],
                    'lname' => $data['lname'] ?? '',
                    'address_line1' => $data['address_line1'],
                    'address_line2' => $data['address_line2'] ?? null,
                    'city' => $data['city'] ?? '',
                    'postcode' => $data['postcode'] ?? '',
                    'country' => $data['country'] ?? 'GB',
                    'phone' => $data['phone'] ?? null,
                    'is_default' => true,
                ]);
            }

            return $customer;
        });

        return (new CustomerSearchResource($customer->load('addresses')))
            ->response()
            ->setStatusCode(201);
    }

    // ------------------------------------------------------------------
    // Discount / Shipping / Notes / Tags
    // ------------------------------------------------------------------

    public function applyDiscount(int $id, ApplyDraftOrderDiscountRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $data = $request->validated();

        $draftOrder = $this->draftOrderService->applyOrderDiscount(
            $draftOrder,
            DiscountType::from($data['discount_type']),
            (float) $data['discount_value']
        );

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    public function removeDiscount(int $id): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $draftOrder = $this->draftOrderService->removeOrderDiscount($draftOrder);

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    public function updateShipping(int $id, UpdateDraftOrderShippingRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $draftOrder = $this->draftOrderService->updateShipping($draftOrder, $request->validated());

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    public function updateNotes(int $id, UpdateDraftOrderNotesRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);
        $data = $request->validated();

        $draftOrder = $this->draftOrderService->updateNotes(
            $draftOrder,
            $data['internal_note'] ?? null,
            $data['customer_note'] ?? null
        );

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    public function syncTags(int $id, SyncDraftOrderTagsRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);

        $draftOrder = $this->draftOrderService->syncTagsByName($draftOrder, $request->validated('tag_names'));

        return $this->freshDraftOrderResource($draftOrder)->response();
    }

    /**
     * All existing tags for the current shop — populates the tags
     * combobox's suggestion list, so users see and reuse existing tags
     * instead of only ever free-typing (which is how near-duplicate tags
     * like "Wholesale" / "wholesale" happen).
     */
    public function listTags(): JsonResponse
    {
        $tags = Ctag::where('shop_id', $this->currentShopId())
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['data' => $tags]);
    }

    /**
     * Real shipping methods for the current shop, replacing the earlier
     * hardcoded placeholder list (DPD/Royal Mail/Collection/etc.) in the
     * Shipping panel. Selecting one lets the frontend auto-fill delivery
     * charge (price) and courier, not just the method name.
     */
    public function listShipMethods(): JsonResponse
    {
        $methods = ShipMethod::where('shop_id', $this->currentShopId())
            ->with('courier')
            ->orderBy('method')
            ->get();

        return ShipMethodResource::collection($methods)->response();
    }

    // ------------------------------------------------------------------
    // Payments
    // ------------------------------------------------------------------

    public function recordPayment(int $id, RecordDraftOrderPaymentRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id);

        $payment = $this->draftOrderService->recordPayment(
            $draftOrder,
            $request->validated(),
            auth()->id()
        );

        return response()->json([
            'payment' => new DraftOrderPaymentResource($payment),
            'draft_order' => $this->freshDraftOrderResource($draftOrder),
        ], 201);
    }

    public function listPayments(int $id): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id, ['payments']);

        return DraftOrderPaymentResource::collection($draftOrder->payments)->response();
    }

    // ------------------------------------------------------------------
    // Product search (for the Add Product dialog)
    // ------------------------------------------------------------------

    public function searchProducts(Request $request): JsonResponse
    {
        $shopId = $this->currentShopId();
        $search = $request->get('q');

        $query = Variant::with('product:product_id,title,featured_image,product_status')
            ->withSum('stocks as stock', 'quantity')
            ->where('shop_id', $shopId);

        // Only sellable products by default — see Product::isActive() for
        // the same product_status === 'Active' assumption used elsewhere.
        // Pass include_inactive=1 to bypass (e.g. an admin tool that
        // deliberately wants to see everything).
        if (! $request->boolean('include_inactive')) {
            $query->whereHas('product', fn ($q) => $q->where('product_status', 'Active'));
        }

        if ($search) {
            $terms = preg_split('/\s+/', trim($search));
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->where(function ($subQ) use ($term) {
                        $subQ->where('sku', 'LIKE', "%{$term}%")
                            ->orWhere('barcode', 'LIKE', "%{$term}%")
                            ->orWhereHas('product', function ($productQ) use ($term) {
                                $productQ->where('title', 'LIKE', "%{$term}%");
                            });
                    });
                }
            });
        }

        if ($productTypeId = $request->get('product_type_id')) {
            $query->whereHas('product', fn ($q) => $q->where('product_type_id', $productTypeId));
        }

        if ($brandId = $request->get('brand_id')) {
            $query->whereHas('product', fn ($q) => $q->where('brand_id', $brandId));
        }

        if ($request->get('in_stock_only')) {
            $query->havingRaw('stock > 0');
        }

        $perPage = min((int) $request->get('per_page', 25), 100);
        $variants = $query->paginate($perPage);

        return VariantSearchResource::collection($variants)->response();
    }

    // ------------------------------------------------------------------
    // Convert to order
    // ------------------------------------------------------------------

    public function convert(int $id, ConvertDraftOrderRequest $request): JsonResponse
    {
        $draftOrder = $this->resolveDraftOrder($id, ['items.variant', 'customer']);
        $data = $request->validated();

        try {
            $order = $this->conversionService->convert(
                $draftOrder,
                auth()->id(),
                (bool) ($data['override_stock'] ?? false),
                (bool) ($data['send_invoice'] ?? false)
            );
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'shortages' => $e->shortages(),
            ], 422);
        } catch (MissingShippingAddressException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'missing_shipping_address' => true,
            ], 422);
        }

        return response()->json([
            'order_id' => $order->order_id,
            'order_number' => $order->order_number,
            'draft_order' => $this->freshDraftOrderResource($draftOrder),
        ]);
    }
}
