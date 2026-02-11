<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    protected $primaryKey = 'business_id';
    protected $fillable = [
        'business_name',
        'address_line1',
        'address_line2',
        'region',
        'postcode',
        'country',
        'reg_no',
        'vat_no',
        'email',
        'phone',
        'whatsapp'
    ];

    public $hidden = ['created_at', 'updated_at'];

    public function shops()
    {
        return $this->belongsTo(BusinessShop::class, 'business_id','business_id');
    }
}
