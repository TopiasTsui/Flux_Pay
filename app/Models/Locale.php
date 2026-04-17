<?php

declare(strict_types=1);

namespace App\Models;

class Locale extends BaseModel
{
    protected $fillable = [
        'code',
        'name',
        'is_default',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function activeCodes(): array
    {
        return static::active()->orderBy('sort_order')->pluck('code')->all();
    }

    public static function defaultCode(): string
    {
        return (string) (static::where('is_default', true)->value('code') ?: config('app.locale', 'en'));
    }
}
