<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;

class PaymentType extends Model
{
    protected $fillable = ['payment_type_code', 'name', 'status', 'sort_order'];

    protected $casts = [
        'status' => EntityStatus::class,
    ];
}
