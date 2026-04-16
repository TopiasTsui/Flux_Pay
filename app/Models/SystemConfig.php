<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class SystemConfig extends Model
{
    use AsSource, Filterable;
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
