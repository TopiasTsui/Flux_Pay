<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class PaymentType extends Model
{
    use AsSource, Filterable;
    protected $fillable = ['payment_type_code', 'name', 'status', 'sort_order'];

    protected $casts = [
        'status' => EntityStatus::class,
    ];
}
