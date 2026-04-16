<?php

namespace App\Models;

use App\Models\Concerns\HasTenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class DepositOrder extends BaseModel
{
    use HasFactory, HasTenantScope;

    protected $fillable = [
        'merchant_id', 'merchant_order_no', 'system_order_no',
        'provider_payment_type_id', 'provider_order_no', 'provider_order_detail_no',
        'order_amount', 'actual_amount', 'merchant_balance_change',
        'merchant_fee', 'provider_fee', 'agent_fee', 'agent_fee_map',
        'provider_agent_fee', 'provider_agent_fee_map',
        'bank_code', 'payer_name', 'sender_account_number', 'sender_account_name',
        'currency', 'status', 'callback_status', 'fund_status', 'fund_at',
        'merchant_notify_url', 'merchant_extra',
        'provider_apply_time', 'provider_callback_time',
        'failed_handler_id', 'failed_handle_time', 'remark',
    ];

    protected $casts = [
        'status' => 'integer',
        'callback_status' => 'integer',
        'fund_status' => 'integer',
        'agent_fee_map' => 'array',
        'provider_agent_fee_map' => 'array',
        'order_amount' => 'decimal:6',
        'actual_amount' => 'decimal:6',
        'merchant_fee' => 'decimal:6',
        'provider_fee' => 'decimal:6',
        'agent_fee' => 'decimal:6',
        'provider_agent_fee' => 'decimal:6',
        'fund_at' => 'datetime',
        'provider_apply_time' => 'datetime',
        'provider_callback_time' => 'datetime',
        'failed_handle_time' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function providerPaymentType(): BelongsTo
    {
        return $this->belongsTo(ProviderPaymentType::class);
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(OrderLog::class, 'orderable');
    }
}
