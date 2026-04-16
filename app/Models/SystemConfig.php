<?php

namespace App\Models;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Cache;

class SystemConfig extends BaseModel
{
    
    protected $fillable = ['group', 'key', 'value', 'remark'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_config:{$key}", 600, function () use ($key, $default) {
            $config = self::where('key', $key)->first();

            return $config ? $config->value : $default;
        });
    }

    public static function setValue(string $key, string $value, string $group = 'general'): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget("system_config:{$key}");
    }
}
