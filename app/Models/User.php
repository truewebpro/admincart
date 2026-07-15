<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Shop;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims():array
    {
        return [];
    }

    public function shops():BelongsToMany
    {
        return $this->belongsToMany(
            Shop::class,
            'shop_users',
            'user_id',
            'shop_id',
            'id',
            'shop_id'
        )
            ->withPivot('role', 'shop_user_status')
            ->withTimestamps();
    }

    public function shopUsers():HasMany
    {
        return $this->hasMany(ShopUser::class)
            ->join('shops', 'shops.shop_id', '=', 'shop_users.shop_id')
            ->select('shops.shop_slug as shop_slug','shop_users.user_id',  'shop_users.role');
    }
}
