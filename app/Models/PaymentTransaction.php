<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    public const TYPE_CHARGE = 'charge';
    public const TYPE_REFUND = 'refund';
    public const TYPE_VOID   = 'void';

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'shop_id', 'order_id', 'parent_id', 'provider', 'type', 'status',
        'gateway_transaction_id', 'amount_minor', 'currency', 'idempotency_key',
        'reason', 'error_code', 'error_message',
        'request_payload', 'response_payload', 'meta', 'created_by',
    ];

    protected $casts = [
        'amount_minor'     => 'integer',
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'meta'             => 'array',
    ];

    // orders uses order_id as its primary key.
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeForShop(Builder $q, int $shopId): Builder
    {
        return $q->where('shop_id', $shopId);
    }

    /** Total already returned to the customer against this charge. */
    public function refundedMinor(): int
    {
        return (int) $this->children()
            ->whereIn('type', [self::TYPE_REFUND, self::TYPE_VOID])
            ->whereIn('status', [self::STATUS_SUCCEEDED, self::STATUS_PENDING])
            ->sum('amount_minor');
    }

    public function refundableMinor(): int
    {
        return max(0, $this->amount_minor - $this->refundedMinor());
    }
}
