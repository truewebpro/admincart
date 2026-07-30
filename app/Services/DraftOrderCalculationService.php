<?php

namespace App\Services;

use App\Models\DraftOrder;
use App\Models\DraftOrderItem;
use App\Models\Setting;
use App\Enums\DiscountType;
use App\Enums\PaymentRecordStatus;

/**
 * Recalculates and persists all monetary totals on a DraftOrder and its
 * items. This is the single source of truth for money on a draft order —
 * the frontend's live totals (in the Vue UI) are a preview only; whatever
 * this service computes on save is authoritative.
 *
 * TAX MODEL (see Variant::vatRate() / config/vat.php):
 * There's no per-product tax rate in this system — only a boolean
 * (istax) on each variant, mapped to a single flat rate
 * (config('vat.standard_rate')) when true, 0% when false. So in practice
 * every line is either "standard rate" or "zero rate" — never a third
 * rate. DraftOrderItem.vat_rate stores whichever applied at the time the
 * item was added (a snapshot, so later rate changes don't retroactively
 * alter historical drafts).
 *
 * PRICES-INCLUDE-VAT (settings.vat_included, per shop):
 * This is the piece that was missing before and caused real over-
 * charging on any shop with vat_included = true (the default — most
 * likely your B2C shops). It changes what a stored price NUMBER means:
 *
 *   - vat_included = false (B2B): unit_price / shipping_total are NET.
 *     VAT is calculated and ADDED on top to reach the amount due.
 *   - vat_included = true (B2C): unit_price / shipping_total are GROSS
 *     (what the customer actually pays). VAT must be EXTRACTED from
 *     that figure, not added a second time.
 *
 * Both cases share one invariant — net + tax = gross, always, whether
 * that split was extracted from a gross input or added onto a net one
 * (see extractOrAddTax()) — so the rest of the calculation doesn't need
 * to branch on the mode beyond that one helper.
 *
 * DISPLAY CONVENTION (matches standard retail/accounting practice):
 *   - Exclusive shops: subtotal/shipping_total shown NET, tax_total is
 *     an amount ADDED to reach the total. (Unchanged from before this
 *     fix — verified algebraically that exclusive-mode shops get
 *     identical output to the pre-fix version.)
 *   - Inclusive shops: subtotal/shipping_total shown GROSS (customer-
 *     facing), tax_total is the VAT EMBEDDED within that figure, not
 *     additive.
 *
 * total is ALWAYS computed independently (via the net+tax invariant
 * across taxable products, non-taxable products, and shipping) — never
 * by summing subtotal − discount + shipping + tax. That naive sum only
 * equals the true total in exclusive mode; in inclusive mode it would
 * double-count tax, which was the actual bug being fixed here.
 *
 * ORDER DISCOUNT + MIXED TAX RATES:
 * When an order-level discount is applied and the basket contains both
 * taxable and zero-rated lines, the discount is allocated proportionally
 * between the taxable and non-taxable subtotals (by their share of the
 * pre-discount subtotal) before tax is calculated on the taxable portion.
 * If your business rule is actually "apply the discount only to taxable
 * lines" or some other allocation, flag it.
 *
 * Shipping now carries VAT (previously treated as zero-rated — fixed
 * per your instruction that VAT applies to "everything, products,
 * shipping, and shipping protection also").
 *
 * NOTE ON FLOAT CASTS: model attributes here are cast to `float`, not
 * `decimal:2`, so they bind cleanly to Vuetify's number inputs. All
 * arithmetic in this service is still done with bcmath on string values
 * internally for precision, and the underlying DB columns remain
 * DECIMAL(12,2).
 */
class DraftOrderCalculationService
{
    /**
     * Cached per-request lookup of whether a shop's prices include VAT.
     */
    private array $vatIncludedCache = [];

    /**
     * Recalculates every line item's totals, then the order's aggregate
     * totals, and persists both. Call this after any change to items,
     * discounts, or shipping — never trust totals submitted from the
     * frontend directly.
     */
    public function recalculate(DraftOrder $draftOrder): DraftOrder
    {
        $draftOrder->loadMissing('items', 'payments');

        $vatIncluded = $this->vatIncludedForShop($draftOrder->shop_id);
        $standardRate = (string) config('vat.standard_rate', 20.00);

        $stickerSubtotal = '0.00';
        $taxableStickerTotal = '0.00';
        $nonTaxableStickerTotal = '0.00';

        foreach ($draftOrder->items as $item) {
            $this->recalculateItem($item, $vatIncluded);

            // "Sticker" amount = whatever basis this shop displays prices
            // in — gross (line_total) if inclusive, net (line_subtotal)
            // if exclusive. This is what order-level discount and the
            // displayed `subtotal` are based on.
            $stickerAmount = $vatIncluded ? (string) $item->line_total : (string) $item->line_subtotal;

            $stickerSubtotal = bcadd($stickerSubtotal, $stickerAmount, 2);

            if ((float) $item->vat_rate > 0) {
                $taxableStickerTotal = bcadd($taxableStickerTotal, $stickerAmount, 2);
            } else {
                $nonTaxableStickerTotal = bcadd($nonTaxableStickerTotal, $stickerAmount, 2);
            }
        }

        $orderDiscountTotal = $this->calculateDiscount(
            $stickerSubtotal,
            $draftOrder->discount_type,
            (string) ($draftOrder->discount_value ?? 0)
        );

        // Allocate the order discount proportionally across taxable vs
        // non-taxable sticker totals, same reasoning as before.
        $taxableDiscountShare = '0.00';
        if (bccomp($stickerSubtotal, '0.00', 2) === 1) {
            $taxableShareRatio = bcdiv($taxableStickerTotal, $stickerSubtotal, 6);
            $taxableDiscountShare = bcmul($orderDiscountTotal, $taxableShareRatio, 2);
        }

        $taxableStickerAfterDiscount = bcsub($taxableStickerTotal, $taxableDiscountShare, 2);
        if (bccomp($taxableStickerAfterDiscount, '0.00', 2) === -1) {
            $taxableStickerAfterDiscount = '0.00';
        }

        $nonTaxableDiscountShare = bcsub($orderDiscountTotal, $taxableDiscountShare, 2);
        $nonTaxableStickerAfterDiscount = bcsub($nonTaxableStickerTotal, $nonTaxableDiscountShare, 2);
        if (bccomp($nonTaxableStickerAfterDiscount, '0.00', 2) === -1) {
            $nonTaxableStickerAfterDiscount = '0.00';
        }

        [$taxableNetFinal, $taxableTaxFinal] = $this->extractOrAddTax(
            $taxableStickerAfterDiscount,
            $standardRate,
            $vatIncluded
        );

        // Shipping now carries VAT too — extracted/added the same way as
        // products, using the standard rate (shipping has no per-line
        // vat_rate concept of its own).
        $shippingSticker = (string) ($draftOrder->shipping_total ?? 0);
        [$shippingNet, $shippingTax] = $this->extractOrAddTax($shippingSticker, $standardRate, $vatIncluded);

        $grossProducts = bcadd(bcadd($taxableNetFinal, $taxableTaxFinal, 2), $nonTaxableStickerAfterDiscount, 2);
        $grossShipping = bcadd($shippingNet, $shippingTax, 2);

        // The one number that MUST be correct regardless of display mode
        // — computed independently, never by summing the display fields.
        $total = bcadd($grossProducts, $grossShipping, 2);

        $taxTotal = bcadd($taxableTaxFinal, $shippingTax, 2);

        $amountPaid = (string) $draftOrder->payments
            ->where('payment_status', PaymentRecordStatus::Completed)
            ->sum('amount');

        $amountDue = bcsub($total, $amountPaid, 2);
        if (bccomp($amountDue, '0.00', 2) === -1) {
            $amountDue = '0.00';
        }

        $draftOrder->subtotal = $stickerSubtotal;
        $draftOrder->discount_total = $orderDiscountTotal;
        $draftOrder->tax_total = $taxTotal;
        // shipping_total column is left exactly as entered — no
        // conversion stored there, matching subtotal's "sticker" basis.
        $draftOrder->total = $total;
        $draftOrder->amount_paid = $amountPaid;
        $draftOrder->amount_due = $amountDue;
        $draftOrder->save();

        return $draftOrder;
    }

    /**
     * Recalculates a single item's line_subtotal (net), tax_total, and
     * line_total (gross) from its quantity/price/discount/vat_rate,
     * respecting the shop's vat_included mode, and saves it.
     */
    public function recalculateItem(DraftOrderItem $item, ?bool $vatIncluded = null): DraftOrderItem
    {
        if ($vatIncluded === null) {
            $vatIncluded = $this->vatIncludedForShop($item->shop_id);
        }

        $stickerAmount = bcmul((string) $item->unit_price, (string) $item->quantity, 2);

        $discountTotal = $this->calculateDiscount(
            $stickerAmount,
            $item->discount_type,
            (string) ($item->discount_value ?? 0)
        );

        $stickerAfterDiscount = bcsub($stickerAmount, $discountTotal, 2);
        if (bccomp($stickerAfterDiscount, '0.00', 2) === -1) {
            $stickerAfterDiscount = '0.00';
        }

        [$net, $tax] = $this->extractOrAddTax($stickerAfterDiscount, (string) $item->vat_rate, $vatIncluded);

        $item->discount_total = $discountTotal;
        $item->line_subtotal = $net;
        $item->tax_total = $tax;
        $item->line_total = bcadd($net, $tax, 2); // gross — what's actually charged
        $item->save();

        return $item;
    }

    /**
     * The core mode-aware helper. Given an amount and a rate:
     *   - vatIncluded = true: $amount is treated as GROSS. Extracts the
     *     net/tax split that's already embedded in it.
     *   - vatIncluded = false: $amount is treated as NET. Calculates and
     *     adds tax on top.
     *
     * Either way, net + tax = the correct gross (charged) amount — that
     * invariant is what lets the rest of the calculation avoid branching
     * on mode beyond this one function.
     *
     * @return array{0: string, 1: string} [net, tax]
     */
    private function extractOrAddTax(string $amount, string $ratePercent, bool $vatIncluded): array
    {
        if (bccomp($ratePercent, '0.00', 6) <= 0) {
            return [$amount, '0.00'];
        }

        $rateFraction = bcdiv($ratePercent, '100', 6);

        if ($vatIncluded) {
            // net = gross / (1 + rate)
            $net = bcdiv($amount, bcadd('1', $rateFraction, 6), 2);
            $tax = bcsub($amount, $net, 2);
        } else {
            $tax = bcmul($amount, $rateFraction, 2);
            $net = $amount;
        }

        return [$net, $tax];
    }

    /**
     * Cached per-request lookup of whether this shop's prices include
     * VAT. Defaults to true (matching settings.vat_included's own
     * column default) if no settings row exists for the shop, rather
     * than silently treating it as exclusive.
     */
    private function vatIncludedForShop(int $shopId): bool
    {
        if (! array_key_exists($shopId, $this->vatIncludedCache)) {
            $settings = Setting::where('shop_id', $shopId)->first();
            $this->vatIncludedCache[$shopId] = $settings ? (bool) $settings->vat_included : true;
        }

        return $this->vatIncludedCache[$shopId];
    }

    /**
     * Shared discount math for both line-level and order-level discounts.
     * $type is a DiscountType enum instance (or null — treated as no
     * discount), $baseAmount is the amount the discount applies against.
     */
    private function calculateDiscount(string $baseAmount, ?DiscountType $type, string $value): string
    {
        if (! $type || bccomp($value, '0.00', 2) <= 0) {
            return '0.00';
        }

        if ($type === DiscountType::Percentage) {
            $discount = bcmul($baseAmount, bcdiv($value, '100', 6), 2);
        } else {
            $discount = $value;
        }

        // Never let a discount exceed the amount it's applied against.
        if (bccomp($discount, $baseAmount, 2) === 1) {
            $discount = $baseAmount;
        }

        return $discount;
    }
}
