<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id',
        'draft_order_id',
        'amount',
        'payment_method',
        'payment_reference',
        'payment_status',
        'paid_at',
        'recorded_by',
        'notes',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'payment_status' => PaymentRecordStatus::class,
        'amount' => 'float',
        'paid_at' => 'datetime',
    ];

    public function draftOrder(): BelongsTo
    {
        return $this->belongsTo(DraftOrder::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

}
