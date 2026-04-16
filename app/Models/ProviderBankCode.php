<?php

namespace App\Models;

use App\Models\BaseModel;

class ProviderBankCode extends BaseModel
{
    
    protected $fillable = ['bank_config_key', 'bank_code', 'provider_bank_code', 'status'];

    protected $casts = ['status' => 'integer'];
}
