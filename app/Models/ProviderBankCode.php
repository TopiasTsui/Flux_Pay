<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class ProviderBankCode extends Model
{
    use AsSource, Filterable;
    protected $fillable = ['bank_config_key', 'bank_code', 'provider_bank_code', 'status'];

    protected $casts = ['status' => EntityStatus::class];
}
