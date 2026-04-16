<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'orderable_type', 'orderable_id', 'action',
        'request_data', 'response_data', 'ip_address', 'remark', 'created_at',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'created_at' => 'datetime',
    ];

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }
}
