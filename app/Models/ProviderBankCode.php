<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Models\BaseModel;

class ProviderBankCode extends BaseModel
{
    
    protected $fillable = ['bank_config_key', 'bank_code', 'provider_bank_code', 'status'];

    protected $casts = ['status' => EntityStatus::class];
}
