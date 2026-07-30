<?php

namespace App\Services;

use App\Enums\DraftOrderStatus;
use App\Enums\PaymentRecordStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\MissingShippingAddressException;
use App\Models\DraftOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderLog;
use App\Models\Shop;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

/**
 * Converts a draft order into a real Order. Distinct from DraftOrderService
 * because this operation is heavier and higher-stakes: it re-checks and
 * deducts stock, generates an order number, and (eventually) triggers
 * invoice generation — none of which belong in the day-to-day draft
 * editing service.
 *
 * Every draft order item is a real catalog product/variant (custom items
 * were removed — order_items.product_id/variant_id are NOT NULL, so a
 * non-catalog line could never be converted anyway), which keeps this
 * straightforward: no item-type branching needed anywhere below.
 *
 * order_number generation matches your actual checkout logic exactly —
 * see generateOrderNumber() below for the specifics.
 */
class DraftOrderConversionService
{
    /**
     * @throws InsufficientStockException if any line is short and $overrideStock is false
     * @throws MissingShippingAddressException if no shipping address has been set
     */
    public function convert(DraftOrder $draftOrder, int $userId, bool $overrideStock = false, bool $sendInvoice = false): Order
    {
        return DB::transaction(function () use ($draftOrder, $userId, $overrideStock, $sendInvoice) {
            $draftOrder->loadMissing('items.variant', 'customer', 'payments');

            $shortages = $this->recheckStock($draftOrder);

            if (! empty($shortages) && ! $overrideStock) {
                throw new InsufficientStockException($shortages);
            }

            if (! $draftOrder->shipping_address_id) {
                throw new MissingShippingAddressException();
            }

            $shop = Shop::where('shop_id', $draftOrder->shop_id)->firstOrFail();
            $orderNumber = $this->generateOrderNumber($shop);

            // orders.payment_method should be an actual method (Cash/Card/
            // Bank Transfer/etc.) — a draft can have several payments, each
            // with its own method, so this uses whichever was most recent.
            // NOT $draftOrder->payment_status->value — that's the status
            // enum (unpaid/paid/partially_paid), a different concept.
            $latestPaymentMethod = $draftOrder->payments
                ->where('payment_status', PaymentRecordStatus::Completed)
                ->sortByDesc('paid_at')
                ->first()
                ?->payment_method
                ?->value;

            $order = Order::create([
                'shop_id' => $draftOrder->shop_id,
                'customer_id' => $draftOrder->customer_id,
                'address_id' => $draftOrder->shipping_address_id,
                'order_number' => $orderNumber,
                'order_status' => 'pending',
                'payment_status' => $draftOrder->payment_status->value,
                'payment_method' => $latestPaymentMethod ?? 'Bank Transfer',
                'fulfillment_status' => 'unfulfilled',
                'shipping_method' => $draftOrder->shipping_method,
                'shipping_cost' => $draftOrder->shipping_total,
                'discount_amount' => $draftOrder->discount_total,
                'subtotal' => $draftOrder->subtotal,
                'order_total' => $draftOrder->total,
                'tax_amount' => $draftOrder->tax_total,
                'currency_code' => $draftOrder->currency,
                'is_guest_order' => false,
                'shipping_name' => $draftOrder->shipping_address['name'] ?? null,
                'shipping_phone' => $draftOrder->shipping_address['phone'] ?? null,
                'shipping_address_line1' => $draftOrder->shipping_address['address_line1'] ?? null,
                'shipping_address_line2' => $draftOrder->shipping_address['address_line2'] ?? null,
                'shipping_city' => $draftOrder->shipping_address['city'] ?? null,
                'shipping_postcode' => $draftOrder->shipping_address['postcode'] ?? null,
                'shipping_country' => $draftOrder->shipping_address['country'] ?? 'GB',
                'notes' => $draftOrder->internal_note,
                'placed_at' => now(),
            ]);

            $this->allocateStockAndCreateOrderItems($draftOrder, $order);

            $draftOrder->status = DraftOrderStatus::Confirmed;
            $draftOrder->confirmed_at = now();
            $draftOrder->converted_order_id = $order->order_id;
            $draftOrder->updated_by = $userId;
            $draftOrder->save();

            OrderLog::create([
                'order_id' => $order->order_id,
                'event' => 'created',
                'description' => "Created from draft order {$draftOrder->draft_number}.",
            ]);

            // NOTE: no invoice is actually generated/sent yet — that's
            // still the TODO below (DraftOrderDocumentService, not built).
            // Logging 'invoice_sent' here would be a FALSE audit entry —
            // claiming an email went out when nothing was dispatched. Once
            // real sending exists, move this OrderLog::create call to fire
            // only after a confirmed successful send, not optimistically
            // alongside the request flag like this.
            //
            // TODO: dispatch invoice generation (queued job — PDF + email),
            // conditional on $sendInvoice (the "send invoice to customer"
            // choice made in the create-order confirmation dialog). Once
            // built, wrap the actual send + the OrderLog::create below in
            // the same success path:
            //
            // if ($sendInvoice) {
            //     // ...generate + send invoice...
            //     OrderLog::create([
            //         'order_id' => $order->order_id,
            //         'event' => 'invoice_sent',
            //         'description' => 'Invoice email sent to customer',
            //     ]);
            // }

            return $order;
        });
    }

    /**
     * Locks each variant's stock row(s) for the duration of the
     * transaction and compares against the draft's requested quantities.
     * Returns an array of shortages (empty if everything's available).
     * Does not deduct anything — that only happens after the caller
     * decides whether to proceed (see convert()).
     */
    private function recheckStock(DraftOrder $draftOrder): array
    {
        $shortages = [];

        foreach ($draftOrder->items as $item) {
            // Defensive only — every item should have a variant_id now
            // that custom items are gone, but don't assume it blindly.
            if (! $item->variant_id) {
                continue;
            }

            $available = (int) Stock::where('variant_id', $item->variant_id)
                ->where('shop_id', $draftOrder->shop_id)
                ->lockForUpdate()
                ->sum('quantity');

            if ($available < $item->quantity) {
                $shortages[] = [
                    'variant_id' => $item->variant_id,
                    'title' => trim($item->title . ' ' . ($item->variant_title ?? '')),
                    'requested' => $item->quantity,
                    'available' => $available,
                ];
            }
        }

        return $shortages;
    }

    /**
     * Matches your existing checkout order-numbering logic exactly
     * (prefix from shops.order_prefix, defaulting to '#'; last order
     * number extracted from the numeric portion of the most recent
     * order's order_number, including soft-deleted ones; 1000 fallback
     * if the shop has no orders yet).
     *
     * Added lockForUpdate() since this already runs inside convert()'s
     * DB transaction — reduces (though doesn't fully eliminate, since
     * other order-creation paths like storefront checkout may not lock
     * the same row) the chance of two concurrent conversions computing
     * the same next number. The numbering algorithm itself is unchanged
     * from what you provided.
     */
    private function generateOrderNumber(Shop $shop): string
    {
        $prefix = $shop->order_prefix ?? '#';

        $lastOrder = Order::withTrashed()
            ->where('shop_id', $shop->shop_id)
            ->orderByDesc('order_id')
            ->lockForUpdate()
            ->first();

        $lastOrderNumber = $lastOrder
            ? (int) preg_replace('/[^0-9]/', '', $lastOrder->order_number)
            : 1000;

        return $prefix . ($lastOrderNumber + 1);
    }

    /**
     * Allocates available stock and creates order_items — mirrors your
     * real checkout logic exactly: allocated = min(available, ordered),
     * only the allocated amount is deducted from stock, and any
     * shortfall becomes backorder_quantity rather than blocking here.
     *
     * The earlier recheckStock()/InsufficientStockException step (see
     * convert()) is what actually blocks conversion, per your
     * instruction — this method itself never blocks. By the time it
     * runs, either stock was sufficient, or the caller explicitly
     * passed $overrideStock — either way, this allocates whatever stock
     * genuinely exists at this exact moment (locked, so no double-spend
     * against a concurrent conversion or checkout), exactly as checkout
     * does, rather than assuming the earlier pre-check is still valid.
     *
     * order_items.total maps to DraftOrderItem.line_subtotal (net,
     * pre-tax) — see prior note: no per-line tax column on order_items,
     * and Order.tax_amount already carries the order-level tax total
     * separately.
     */
    private function allocateStockAndCreateOrderItems(DraftOrder $draftOrder, Order $order): void
    {
        foreach ($draftOrder->items as $item) {
            $stock = Stock::where('variant_id', $item->variant_id)
                ->where('shop_id', $draftOrder->shop_id)
                ->lockForUpdate()
                ->first();

            $available = $stock ? $stock->quantity : 0;
            $ordered = $item->quantity;
            $allocated = min($available, $ordered);
            $backorder = $ordered - $allocated;

            if ($stock && $allocated > 0) {
                $stock->decrement('quantity', $allocated);
            }

            OrderItem::create([
                'order_id' => $order->order_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'title' => trim($item->title . ($item->variant_title ? ' - ' . $item->variant_title : '')),
                'options' => $this->formatVariantOptions($item->variant?->option_values),
                'price' => $item->unit_price,
                'quantity' => $ordered,
                'total' => $item->line_subtotal,
                'allocated_quantity' => $allocated,
                'backorder_quantity' => $backorder,
                'shipped_quantity' => 0,
            ]);
        }
    }

    /**
     * Reshapes Variant::option_values (stored as a plain associative map,
     * e.g. {"Flavour": "Black Mamba"} — same shape Variant::getDisplayTitleAttribute()
     * reads) into the array-of-objects format order_items.options expects:
     * [{"name": "Flavour", "value": "Black Mamba"}].
     *
     * @return array<int, array{name: string, value: mixed}>
     */
    private function formatVariantOptions(?array $optionValues): array
    {
        if (empty($optionValues)) {
            return [];
        }

        return collect($optionValues)
            ->map(fn ($value, $name) => ['name' => $name, 'value' => $value])
            ->values()
            ->all();
    }
}
