<?php

namespace App\Models;

use App\Enums\WalletOperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantWalletRecord extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'merchant_id', 'sn', 'type_code', 'amount',
        'pre_total_balance', 'pre_available_balance', 'pre_hold_balance',
        'total_balance', 'available_balance', 'hold_balance',
        'system_order_no', 'related_type', 'related_id',
        'remark', 'remark_view', 'created_by', 'created_at',
    ];

    protected $casts = [
        'type_code' => WalletOperationType::class,
        'amount' => 'decimal:6',
        'created_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
