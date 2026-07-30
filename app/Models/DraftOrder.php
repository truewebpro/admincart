<?php

namespace App\Models;

use App\Enums\DiscountType;
use App\Enums\DraftOrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DraftOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // shop_id IS fillable — the trait that used to auto-stamp it is
        // gone. Set it explicitly in the controller from session('shop_id'),
        // e.g. DraftOrder::create(['shop_id' => session('shop_id'), ...]).
        // Never take shop_id from request input directly.
        'shop_id',
        'draft_number',
        'customer_id',
        'status',
        'payment_status',
        'currency',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_total',
        'shipping_total',
        'tax_total',
        'total',
        'amount_paid',
        'amount_due',
        'internal_note',
        'customer_note',
        'shipping_method',
        'courier',
        'requested_delivery_date',
        'collection_only',
        'billing_same_as_shipping',
        'billing_address',
        'shipping_address',
        'shipping_address_id',
        'created_by',
        'updated_by',
        'confirmed_at',
        'converted_order_id',
    ];

    protected $casts = [
        'status' => DraftOrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'discount_type' => DiscountType::class,
        'subtotal' => 'float',
        'discount_value' => 'float',
        'discount_total' => 'float',
        'shipping_total' => 'float',
        'tax_total' => 'float',
        'total' => 'float',
        'amount_paid' => 'float',
        'amount_due' => 'float',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'collection_only' => 'boolean',
        'billing_same_as_shipping' => 'boolean',
        'requested_delivery_date' => 'date',
        'confirmed_at' => 'datetime',
    ];

    // ------------------------------------------------------------------
    // Relationships
    // ------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class, 'shipping_address_id', 'address_id');
    }


    /**
     * @return HasMany<DraftOrderItem>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DraftOrderItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(DraftOrderPayment::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Ctag::class, 'draft_order_ctag', 'draft_order_id', 'ctag_id')
            ->using(DraftOrderCtag::class)
            ->withTimestamps();
    }

    // activityLogs() relation removed for now — DraftOrderActivityLog /
    // draft_order_activity_logs are not part of this build yet. Re-add
    // both when/if audit logging is needed.

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id', 'order_id');
    }

    // ------------------------------------------------------------------
    // Convenience / state helpers
    // ------------------------------------------------------------------

    public function isDraft(): bool
    {
        return $this->status === DraftOrderStatus::Draft;
    }

    public function isConverted(): bool
    {
        return ! is_null($this->converted_order_id);
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }
}
