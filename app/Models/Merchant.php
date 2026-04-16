<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use App\Models\Concerns\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends BaseModel
{
    use HasFactory, HasTenantScope, HasWallet;

    protected $fillable = [
        'agent_id', 'code', 'name', 'md5key', 'currency_code', 'status',
        'total_balance', 'available_balance', 'hold_balance',
        'white_ips', 'options',
    ];

    protected $casts = [
        'status' => 'integer',
        'white_ips' => 'array',
        'options' => 'array',
        'total_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'hold_balance' => 'decimal:2',
    ];

    protected $hidden = ['md5key'];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function merchantPaymentTypes(): HasMany
    {
        return $this->hasMany(MerchantPaymentType::class);
    }

    public function merchantProviderPaymentTypes(): HasMany
    {
        return $this->hasMany(MerchantProviderPaymentType::class);
    }

    public function depositOrders(): HasMany
    {
        return $this->hasMany(DepositOrder::class);
    }

    public function withdrawOrders(): HasMany
    {
        return $this->hasMany(WithdrawOrder::class);
    }

    public function walletRecords(): HasMany
    {
        return $this->hasMany(MerchantWalletRecord::class);
    }
}
