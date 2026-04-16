<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    protected $fillable = ['type', 'value', 'remark', 'status'];

    protected $casts = ['status' => EntityStatus::class];
}
