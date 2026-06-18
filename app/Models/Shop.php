<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Cashier\Billable;
use App\Models\ShopUser;
use App\Models\User;


class Shop extends Model
{
    use HasFactory, Billable;
    protected $primaryKey = 'shop_id';
    public $incrementing = true;
    protected $fillable = [
        'shop_name',
        'shop_slug',
        'subdomain',
        'maindomain',
        'shop_status',
        'plan_slug',
    ];

    public $hidden = ['created_at','updated_at'];

    public function getKeyName()
    {
        return 'shop_id';
    }

    protected static function booted()
    {
//        parent::boot();
        static::created(function ($shop) {
            $superAdmins = ShopUser::where('role','superadmin')
                ->pluck('user_id')->unique();

            if ($superAdmins->isEmpty()) {
                return;
            }
            foreach ($superAdmins as $userId) {
                ShopUser::firstOrCreate([
                    'shop_id' => $shop->shop_id,
                    'user_id' => $userId,
                ],[
                    'role' => 'superadmin',
                    'shop_user_status' => 'Active',
                ]);
            }
        });
    }

    public function users():BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'shop_users',
            'shop_id',
            'user_id',
            'shop_id',
            'id'
        )->withPivot('role', 'shop_user_status')
            ->withTimestamps();
    }

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

    public function mailtrapList():HasOne
    {
        return $this->hasOne(
            ShopMailtrapList::class,
            'shop_id',
            'shop_id'
        );
    }

    public function sendcloud(): HasOne
    {
        return $this->hasOne(SendCloud::class, 'shop_id', 'shop_id');
    }
}
