<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcartCoupon extends Model
{
    use HasFactory;
    protected $table = 'acart_coupons';
    protected $primaryKey = 'id';
    protected $fillable = [
        'acart_id',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'type',
        'value',
        'priority',
        'shop_id'
    ];

    // is_auto/label are derived from the related Coupon rather than stored
    // columns, so they can never drift out of sync with the coupon itself.
    protected $appends = ['is_auto', 'label'];

    public function coupon():BelongsTo
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'coupon_id');
    }

    public function getIsAutoAttribute():bool
    {
        return (bool) ($this->coupon?->is_auto ?? false);
    }

    public function getLabelAttribute()
    {
        return $this->coupon?->label ?? $this->coupon_code;
    }
}
