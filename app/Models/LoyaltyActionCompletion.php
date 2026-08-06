<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoyaltyActionCompletion extends Model
{
    use HasFactory;

    protected $fillable = [
        'shop_id', 'cshop_id', 'customer_id', 'loyalty_earn_action_id',
        'reference_type', 'reference_id', 'proof_url',
        'status', 'points_awarded', 'loyalty_transaction_id',
        'admin_notes', 'reviewed_by_admin_id', 'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function action()
    {
        return $this->belongsTo(LoyaltyEarnAction::class, 'loyalty_earn_action_id');
    }

    public function customerShop()
    {
        return $this->belongsTo(CustomerShop::class, 'cshop_id', 'cshop_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function loyaltyTransaction()
    {
        return $this->belongsTo(LoyaltyTransaction::class);
    }

    public function reviewedByAdmin()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by_admin_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
