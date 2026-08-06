<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreCreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'cshop_id', 'customer_id', 'type', 'source', 'amount', 'balance_after',
        'order_id', 'loyalty_transaction_id', 'notes',
        'created_by_type', 'created_by_admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // The real relation — balance lives on customer_shops, scoped per shop.
    public function customerShop()
    {
        return $this->belongsTo(CustomerShop::class, 'cshop_id', 'cshop_id');
    }

    // Denormalized convenience relation — the global customer record.
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function loyaltyTransaction()
    {
        return $this->belongsTo(LoyaltyTransaction::class);
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_admin_id');
    }

    public function scopeForShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }
}
