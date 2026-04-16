<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class ProviderBankCode extends Model
{
    protected $fillable = ['bank_config_key', 'bank_code', 'provider_bank_code', 'status'];

    protected $casts = ['status' => EntityStatus::class];
}
