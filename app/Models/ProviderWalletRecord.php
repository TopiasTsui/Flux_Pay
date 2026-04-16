<?php

namespace App\Models;

use App\Enums\WalletOperationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ProviderWalletRecord extends Model
{
    use AsSource, Filterable;

    public $timestamps = false;

    protected $fillable = [
        'provider_id', 'sn', 'type_code', 'amount',
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

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
