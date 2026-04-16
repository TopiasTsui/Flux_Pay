<?php

namespace App\Models;

use App\Enums\EntityStatus;
use Illuminate\Database\Eloquent\Model;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Blacklist extends Model
{
    use AsSource, Filterable;
    protected $fillable = ['type', 'value', 'remark', 'status'];

    protected $casts = ['status' => EntityStatus::class];
}
