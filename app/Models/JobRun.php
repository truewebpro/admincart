<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobRun extends Model
{
    protected $fillable = [
        'shop_id',
        'job_type',
        'status',
        'started_at',
        'finished_at',
        'result_summary',
        'error_message',
    ];

    protected $casts = [
        'result_summary' => 'array',
        'started_at'      => 'datetime',
        'finished_at'     => 'datetime',
    ];
}
