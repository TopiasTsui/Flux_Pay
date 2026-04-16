<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentType extends BaseModel
{
    
    protected $fillable = ['payment_type_code', 'name', 'status', 'sort_order'];

    protected $casts = [
        'status' => 'integer',
    ];
}
