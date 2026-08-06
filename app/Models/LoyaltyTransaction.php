<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'cshop_id', 'customer_id', 'type', 'points', 'balance_after',
        'order_id', 'loyalty_redeem_rule_id', 'store_credit_transaction_id',
        'notes', 'created_by_type', 'created_by_admin_id',
    ];

    public function customerShop()
    {
        return $this->belongsTo(CustomerShop::class, 'cshop_id', 'cshop_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function redeemRule()
    {
        return $this->belongsTo(LoyaltyRedeemRule::class, 'loyalty_redeem_rule_id');
    }

    public function storeCreditTransaction()
    {
        return $this->belongsTo(StoreCreditTransaction::class);
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
