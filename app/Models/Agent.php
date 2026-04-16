<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use App\Models\Concerns\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends BaseModel
{
    use HasFactory, HasTenantScope, HasWallet;

    protected $fillable = [
        'parent_id', 'types', 'name', 'level', 'status', 'currency',
        'total_balance', 'available_balance', 'hold_balance',
        'created_by',
    ];

    protected $casts = [
        'types' => 'string',
        'status' => 'integer',
        'level' => 'integer',
        'total_balance' => 'decimal:2',
        'available_balance' => 'decimal:2',
        'hold_balance' => 'decimal:2',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function merchants(): HasMany
    {
        return $this->hasMany(Merchant::class);
    }

    public function providers(): HasMany
    {
        return $this->hasMany(Provider::class);
    }

    public function walletRecords(): HasMany
    {
        return $this->hasMany(AgentWalletRecord::class);
    }
}
