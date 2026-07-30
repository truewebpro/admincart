<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\DraftOrderItemType;
use App\Enums\DraftOrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\Ctag;
use App\Models\CustomerAddress;
use App\Models\DraftOrder;
use App\Models\DraftOrderItem;
use App\Models\DraftOrderPayment;
use App\Models\Product;
use App\Models\Shop;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;

/**
 * Core draft order mutations. Every method that changes money-relevant
 * state (items, discounts, shipping, payments) ends by calling
 * DraftOrderCalculationService::recalculate() — never trust a caller to
 * remember to do that separately.
 *
 * Does NOT handle converting a draft into a real Order — that's a
 * distinct, more involved operation (stock deduction, order numbering,
 * invoice generation) and belongs in its own DraftOrderConversionService.
 *
 * shop_id is never read from request input here — every method takes it
 * (or a DraftOrder that already has it) as an explicit parameter, which
 * the controller is responsible for sourcing from session('shop_id').
 */
class DraftOrderService
{
    public function __construct(
        private readonly DraftOrderCalculationService $calculationService,
        private readonly DraftNumberService $draftNumberService,
    ) {
    }

    /**
     * Creates a new draft order shell. Per the "don't create an empty
     * draft on page load" requirement, callers should only invoke this
     * on the first meaningful change or an explicit Save Draft action —
     * that timing decision belongs to the controller, not here.
     */
    public function createDraft(int $shopId, int $userId, array $attributes = []): DraftOrder
    {
        return DB::transaction(function () use ($shopId, $userId, $attributes) {
            $shop = Shop::where('shop_id', $shopId)->firstOrFail();
            $draftNumber = $this->draftNumberService->next($shop);

            return DraftOrder::create(array_merge([
                'shop_id' => $shopId,
                'draft_number' => $draftNumber,
                'status' => DraftOrderStatus::Draft,
                'payment_status' => PaymentStatus::Unpaid,
                'currency' => 'GBP',
                'created_by' => $userId,
            ], $attributes));
        });
    }

    /**
     * Clones a draft order: same customer, notes, shipping, discount, and
     * items — but a fresh draft number, Draft status, Unpaid status, no
     * payments, and no confirmed_at/converted_order_id carried over.
     */
    public function duplicateDraft(DraftOrder $source, int $userId): DraftOrder
    {
        return DB::transaction(function () use ($source, $userId) {
            $source->loadMissing('items', 'tags');

            $copy = $this->createDraft($source->shop_id, $userId, [
                'customer_id' => $source->customer_id,
                'currency' => $source->currency,
                'internal_note' => $source->internal_note,
                'customer_note' => $source->customer_note,
                'shipping_method' => $source->shipping_method,
                'courier' => $source->courier,
                'requested_delivery_date' => $source->requested_delivery_date,
                'collection_only' => $source->collection_only,
                'billing_same_as_shipping' => $source->billing_same_as_shipping,
                'billing_address' => $source->billing_address,
                'shipping_address' => $source->shipping_address,
                'discount_type' => $source->discount_type,
                'discount_value' => $source->discount_value,
                'shipping_total' => $source->shipping_total,
            ]);

            foreach ($source->items as $sourceItem) {
                $item = $sourceItem->replicate([
                    'discount_total', 'tax_total', 'line_subtotal', 'line_total',
                ]);
                $item->draft_order_id = $copy->id;
                // NOT save() — line_subtotal/line_total are NOT NULL with no
                // default, and replicate() excluded them above. recalculateItem()
                // computes and saves in one step, avoiding a save() on an
                // incomplete row.
                $this->calculationService->recalculateItem($item);
            }

            $copy->tags()->sync($source->tags->pluck('id'));

            return $this->recalculate($copy);
        });
    }

    /**
     * Adds a product/variant line, or increases quantity on the existing
     * line if this variant is already on the draft (per your spec: same
     * variant → increment quantity, don't duplicate).
     *
     * Stock is checked but never blocks — returns a warning string
     * alongside the item instead of throwing, per your instruction that
     * this is warn-only for now.
     *
     * @return array{item: DraftOrderItem, stock_warning: ?string}
     */
    public function addProductItem(
        DraftOrder $draftOrder,
        Variant $variant,
        int $quantity,
        ?float $unitPriceOverride = null,
        ?array $discount = null,
    ): array {
        return DB::transaction(function () use ($draftOrder, $variant, $quantity, $unitPriceOverride, $discount) {
            $existing = $draftOrder->items()
                ->where('item_type', DraftOrderItemType::Product->value)
                ->where('variant_id', $variant->variant_id)
                ->first();

            if ($existing) {
                $item = $existing;
                $item->quantity += $quantity;
            } else {
                $product = $variant->product ?? Product::where('product_id', $variant->product_id)->first();

                $item = new DraftOrderItem([
                    'shop_id' => $draftOrder->shop_id,
                    'draft_order_id' => $draftOrder->id,
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->variant_id,
                    'item_type' => DraftOrderItemType::Product,
                    'title' => $product?->title ?? 'Unknown product',
                    'variant_title' => $variant->display_title,
                    'sku' => $variant->sku,
                    'barcode' => $variant->barcode,
                    'product_image' => $variant->variant_image ?? $product?->featured_image,
                    'quantity' => $quantity,
                    'unit_cost' => $variant->costprice,
                    'unit_price' => $unitPriceOverride ?? $variant->price,
                    'vat_rate' => $variant->vatRate(),
                    'discount_type' => $discount['type'] ?? null,
                    'discount_value' => $discount['value'] ?? 0,
                    'stock_snapshot' => $variant->currentStock(),
                    'sort_order' => $draftOrder->items()->count(),
                ]);
                // NOT $draftOrder->items()->save($item) — line_subtotal/
                // line_total are NOT NULL with no default, and aren't set
                // until recalculateItem() runs below. Saving here first
                // would fail with exactly the "doesn't have a default
                // value" error.
            }

            if ($unitPriceOverride !== null) {
                $item->unit_price = $unitPriceOverride;
            }
            if ($discount !== null) {
                $item->discount_type = $discount['type'] ?? null;
                $item->discount_value = $discount['value'] ?? 0;
            }

            // Computes line_subtotal/tax_total/line_total AND saves — the
            // only save this item needs, whether it's a new INSERT or an
            // existing row's UPDATE (quantity increment). Doing this before
            // the order-level recalculate() below also fixes a second,
            // quieter bug: without it, the merged item's incremented
            // quantity would only exist in memory when the order-level
            // recalculation re-fetches items from the database, silently
            // using the old, pre-increment quantity instead.
            $this->calculationService->recalculateItem($item);

            $draftOrder = $this->recalculate($draftOrder);
            $item->refresh();

            return [
                'item' => $item,
                'stock_warning' => $this->stockWarning($variant, $item->quantity),
            ];
        });
    }

    public function updateItem(DraftOrderItem $item, array $data): DraftOrderItem
    {
        return DB::transaction(function () use ($item, $data) {
            $item->fill(array_intersect_key($data, array_flip([
                'quantity', 'unit_price', 'discount_type', 'discount_value',
            ])));
            $item->save();

            $this->recalculate($item->draftOrder);

            return $item->refresh();
        });
    }

    public function removeItem(DraftOrderItem $item): DraftOrder
    {
        return DB::transaction(function () use ($item) {
            $draftOrder = $item->draftOrder;
            $item->delete();

            return $this->recalculate($draftOrder);
        });
    }

    public function applyOrderDiscount(DraftOrder $draftOrder, ?DiscountType $type, float $value): DraftOrder
    {
        $draftOrder->discount_type = $type;
        $draftOrder->discount_value = $value;
        $draftOrder->save();

        return $this->recalculate($draftOrder);
    }

    public function removeOrderDiscount(DraftOrder $draftOrder): DraftOrder
    {
        return $this->applyOrderDiscount($draftOrder, null, 0);
    }

    public function updateShipping(DraftOrder $draftOrder, array $data): DraftOrder
    {
        $draftOrder->fill(array_intersect_key($data, array_flip([
            'shipping_method', 'courier', 'shipping_total', 'requested_delivery_date',
            'collection_only', 'billing_same_as_shipping', 'billing_address', 'shipping_address',
        ])));
        $draftOrder->save();

        return $this->recalculate($draftOrder);
    }

    /**
     * Attaches a real CustomerAddress to the draft order — sets
     * shipping_address_id (the FK conversion needs for orders.address_id)
     * AND snapshots it into shipping_address (JSON), so the order isn't
     * retroactively affected if the customer edits their address later.
     * If billing_same_as_shipping, mirrors the same snapshot into
     * billing_address too.
     */
    /**
     * Attaches a customer, clearing any previously-set shipping address
     * whenever the customer actually changes (not on a no-op re-select of
     * the same customer). An address belongs to one specific customer —
     * carrying it over to a different customer would silently attach the
     * wrong person's address to the order.
     */
    public function setCustomer(DraftOrder $draftOrder, int $customerId): DraftOrder
    {
        if ($draftOrder->customer_id !== $customerId) {
            $draftOrder->shipping_address_id = null;
            $draftOrder->shipping_address = null;
            if ($draftOrder->billing_same_as_shipping) {
                $draftOrder->billing_address = null;
            }
        }

        $draftOrder->customer_id = $customerId;
        $draftOrder->save();

        return $draftOrder;
    }

    /**
     * Detaches the customer AND clears the shipping address — an address
     * tied to a customer that's no longer on the draft has no business
     * staying attached (same reasoning as setCustomer() above).
     */
    public function removeCustomer(DraftOrder $draftOrder): DraftOrder
    {
        $draftOrder->customer_id = null;
        $draftOrder->shipping_address_id = null;
        $draftOrder->shipping_address = null;
        if ($draftOrder->billing_same_as_shipping) {
            $draftOrder->billing_address = null;
        }
        $draftOrder->save();

        return $draftOrder;
    }

    public function setShippingAddress(DraftOrder $draftOrder, CustomerAddress $address): DraftOrder
    {
        // Built inline rather than depending on a toDraftOrderAddressArray()
        // method on CustomerAddress — that was an optional convenience
        // suggested earlier that wasn't actually added to the model, so
        // this keeps the service self-contained instead of assuming it exists.
        $snapshot = [
            'name' => trim($address->fname . ' ' . $address->lname),
            'address_line1' => $address->address_line1,
            'address_line2' => $address->address_line2,
            'city' => $address->city,
            'postcode' => $address->postcode,
            'country' => $address->country,
            'phone' => $address->phone,
        ];

        $draftOrder->shipping_address_id = $address->address_id;
        $draftOrder->shipping_address = $snapshot;

        if ($draftOrder->billing_same_as_shipping) {
            $draftOrder->billing_address = $snapshot;
        }

        $draftOrder->save();

        return $draftOrder;
    }

    public function updateNotes(DraftOrder $draftOrder, ?string $internalNote, ?string $customerNote): DraftOrder
    {
        $draftOrder->internal_note = $internalNote;
        $draftOrder->customer_note = $customerNote;
        $draftOrder->save();

        return $draftOrder;
    }

    /**
     * @param array<int> $ctagIds
     */
    /**
     * Resolves tag names into Ctag rows (creating any that don't exist
     * yet, scoped to this draft order's shop), then syncs the draft
     * order's tags to exactly that set. Matches case-insensitively so
     * "Wholesale" and "wholesale" don't end up as two separate tags.
     *
     * @param array<string> $tagNames
     */
    public function syncTagsByName(DraftOrder $draftOrder, array $tagNames): DraftOrder
    {
        $ctagIds = collect($tagNames)
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique(fn ($name) => strtolower($name))
            ->map(function ($name) use ($draftOrder) {
                $ctag = Ctag::where('shop_id', $draftOrder->shop_id)
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->first();

                if (! $ctag) {
                    $ctag = Ctag::create([
                        'shop_id' => $draftOrder->shop_id,
                        'name' => $name,
                    ]);
                }

                return $ctag->id;
            })
            ->all();

        $draftOrder->tags()->sync($ctagIds);

        return $draftOrder;
    }

    /**
     * Records a payment and recalculates. Defaults the payment's own
     * status to Completed (i.e. money has actually been received right
     * now) rather than Pending — ASSUMPTION: "Record payment" in the UI
     * means confirming a payment that's already happened, not logging an
     * expected future one. Flag if Pending should be the default instead.
     */
    public function recordPayment(DraftOrder $draftOrder, array $data, int $userId): DraftOrderPayment
    {
        return DB::transaction(function () use ($draftOrder, $data, $userId) {
            $payment = DraftOrderPayment::create([
                'shop_id' => $draftOrder->shop_id,
                'draft_order_id' => $draftOrder->id,
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? PaymentMethod::Other,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_status' => $data['payment_status'] ?? PaymentRecordStatus::Completed,
                'paid_at' => $data['paid_at'] ?? now(),
                'recorded_by' => $userId,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recalculate($draftOrder);

            return $payment;
        });
    }

    /**
     * Recalculates totals via DraftOrderCalculationService, then derives
     * payment_status from the resulting amount_paid vs total. Centralised
     * here so every mutating method above stays consistent without each
     * one having to remember the status transition logic itself.
     */
    private function recalculate(DraftOrder $draftOrder): DraftOrder
    {
        $draftOrder = $this->calculationService->recalculate($draftOrder);

        $draftOrder->payment_status = match (true) {
            $draftOrder->amount_paid <= 0 => PaymentStatus::Unpaid,
            $draftOrder->amount_paid >= $draftOrder->total && $draftOrder->total > 0 => PaymentStatus::Paid,
            default => PaymentStatus::PartiallyPaid,
        };
        $draftOrder->save();

        return $draftOrder;
    }

    /**
     * Warn-only stock check (per your instruction — never blocks the
     * add/update itself). Returns null if stock is sufficient.
     */
    private function stockWarning(Variant $variant, int $requestedQuantity): ?string
    {
        $available = $variant->currentStock();

        if ($available <= 0) {
            return 'Out of stock';
        }

        if ($requestedQuantity > $available) {
            return "Insufficient stock ({$available} available)";
        }

        return null;
    }
}
