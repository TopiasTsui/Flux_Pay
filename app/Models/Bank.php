<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\BaseModel;

class Bank extends BaseModel
{
    
    protected $fillable = ['bank_code', 'name', 'status', 'sort_order'];

    protected $casts = ['status' => EntityStatus::class];
}
