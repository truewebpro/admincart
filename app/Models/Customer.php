<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable  implements JWTSubject
{
    use HasFactory;
    protected $primaryKey = 'customer_id';
    protected $table = 'customers';
    protected $fillable = [
        'fname',
        'lname',
        'email',
        'password',
        'phone',
        'status',
    ];
    protected $hidden = ['password'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id', 'customer_id');
    }

    public function defaultaddress():HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id', 'customer_id')
            ->where('is_default', true);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id', 'customer_id');
    }

    public function shops(): HasMany
    {
        return $this->hasMany(CustomerShop::class, 'customer_id', 'customer_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Ctag::class, 'customer_ctag', 'customer_id', 'ctag_id', 'customer_id')
            ->using(CustomerCtag::class)
            ->withTimestamps();
    }

    public function draftOrders(): HasMany
    {
        return $this->hasMany(DraftOrder::class, 'customer_id', 'customer_id');
    }

    /**
     * The business profile for this customer within the currently resolved
     * shop. Uses App\Support\CurrentShop, which checks app('currentShop')
     * first, then falls back to — matching what
     * ResolveAdminShop actually sets, rather than assuming a container
     * binding that isn't wired up yet. Returns null if the customer has
     * no customer_shops row (and therefore no profile) for this shop.
     */
    public function businessProfileForCurrentShop(): ?CustomerBusinessProfile
    {
        $shopId = session('shop_id');

        if (! $shopId) {
            return null;
        }

        $customerShop = $this->shops()->where('shop_id', $shopId)->first();

        return $customerShop
            ? CustomerBusinessProfile::where('cshop_id', $customerShop->cshop_id)->first()
            : null;
    }


    // ------------------------------------------------------------------
    // Scopes
    // ------------------------------------------------------------------

    /**
     * Restrict to customers who have a customer_shops row for the given
     * shop — i.e. customers actually known to/tradeable by this shop.
     * This is the primary scoping mechanism for customer search, since
     * there's no shop_id column on customers itself.
     */
    public function scopeForShop(Builder $query, Shop $shop): Builder
    {
        return $query->whereHas('shops', function (Builder $q) use ($shop) {
            $q->where('shop_id', $shop->getKey())->where('status', 'active');
        });
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->fname . ' ' . $this->lname);
    }
}
