<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use App\Models\Concerns\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends BaseModel
{
    use HasFactory, HasTenantScope, HasWallet;

    protected $fillable = [
        'agent_id', 'name', 'provider_no', 'vendor_id', 'vendor_meta',
        'bank_config_key', 'currency_code', 'status',
        'total_balance', 'available_balance', 'hold_balance',
        'api_available_balance', 'api_hold_balance',
        'call_back_ips', 'options',
    ];

    protected $casts = [
        'status' => 'integer',
        'vendor_meta' => 'array',
        'options' => 'array',
        'total_balance' => 'decimal:6',
        'available_balance' => 'decimal:6',
        'hold_balance' => 'decimal:6',
        'api_available_balance' => 'decimal:6',
        'api_hold_balance' => 'decimal:6',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function providerPaymentTypes(): HasMany
    {
        return $this->hasMany(ProviderPaymentType::class);
    }

    public function walletRecords(): HasMany
    {
        return $this->hasMany(ProviderWalletRecord::class);
    }
}
