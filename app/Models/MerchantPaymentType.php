<?php

namespace App\Models;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class MerchantPaymentType extends Model
{
    use AsSource, Filterable;
    protected $fillable = [
        'merchant_id', 'payment_type_id', 'status',
        'single_min_amount', 'single_max_amount',
        'deposit_fee_type', 'deposit_fee', 'deposit_agents_fee',
        'withdraw_fee_type', 'withdraw_fee', 'withdraw_agents_fee',
    ];

    protected $casts = [
        'status' => EntityStatus::class,
        'deposit_fee_type' => FeeType::class,
        'withdraw_fee_type' => FeeType::class,
        'deposit_agents_fee' => 'array',
        'withdraw_agents_fee' => 'array',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class);
    }
}
