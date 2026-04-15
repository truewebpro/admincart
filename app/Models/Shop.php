<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;

class Shop extends Model
{
    use HasFactory, Billable;
    protected $primaryKey = 'shop_id';
    protected $fillable = [
        'shop_name',
        'shop_slug',
        'subdomain',
        'maindomain',
        'shop_status',
        'plan_slug',
    ];

    public $hidden = ['created_at','updated_at'];

    public function shippingMethods()
    {
        return $this->belongsToMany(ShippingMethod::class, 'shop_shipping_methods', 'shop_id', 'shop_id')
            ->withPivot(['custom_cost', 'is_enabled', 'handling_fee','priority']);
    }

    public function Order()
    {
        return $this->hasMany(Order::class, 'shop_id', 'shop_id');
    }

    public function setting()
    {
        return $this->hasOne(Setting::class, 'shop_id', 'shop_id');
    }

    public function reviews()
    {
        return $this->hasMany(Proreview::class, 'shop_id', 'shop_id');
    }

    public function subscribe_section()
    {
        return $this->hasOne(SubscribeSection::class, 'shop_id', 'shop_id');
    }

    public function plan():BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_slug', 'slug');
    }

    public function subscriptions():HasMany
    {
        return $this->hasMany(\Laravel\Cashier\Subscription::class, 'shop_id', 'shop_id');
    }

    public function subscriptionItems():HasMany
    {
        return $this->hasMany(\Laravel\Cashier\SubscriptionItem::class, 'shop_id', 'shop_id');
    }
}
