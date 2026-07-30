<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DraftOrderCtag extends Pivot
{
    protected $table = 'draft_order_ctag';

    public $incrementing = false;

    protected $fillable = [
        'draft_order_id',
        'ctag_id',
    ];
}
