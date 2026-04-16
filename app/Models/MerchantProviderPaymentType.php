<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantProviderPaymentType extends Model
{
    protected $fillable = ['merchant_id', 'provider_payment_type_id', 'status', 'remark'];

    protected $casts = [
        'status' => EntityStatus::class,
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function providerPaymentType(): BelongsTo
    {
        return $this->belongsTo(ProviderPaymentType::class);
    }
}
