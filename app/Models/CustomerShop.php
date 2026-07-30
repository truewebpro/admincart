<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerShop extends Model
{
    use HasFactory;
    protected $primaryKey = 'cshop_id';
    protected $table = 'customer_shops';
    protected $fillable = [
        'ctags',
        'customer_id',
        'shop_id',
        'thirdparty_id',
        'status',
        'registered_at',
        'mailtrap_contact_id',
        'mailtrap_synced',
        'mailtrap_synced_at',
        'mailtrap_last_error',
    ];

    protected $casts = [
        'ctags' => 'array',
        'registered_at' => 'datetime',
        'mailtrap_synced' => 'boolean',
        'mailtrap_synced_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if(empty($model->ctags)) {
                $model->ctags = ['b2c'];
            }
        });
    }

    public function customer():BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function shop():BelongsTo
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    public function businessProfile(): HasOne
    {
        return $this->hasOne(CustomerBusinessProfile::class, 'cshop_id', 'cshop_id');
    }

}
