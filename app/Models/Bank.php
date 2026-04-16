<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = ['bank_code', 'name', 'status', 'sort_order'];

    protected $casts = ['status' => EntityStatus::class];
}
