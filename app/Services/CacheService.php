<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CacheService
{
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            if (! self::isRedisAvailable()) {
                return $callback();
            }

            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning('Cache read failed, falling back to callback', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    public static function forget(string $key): bool
    {
        try {
            if (! self::isRedisAvailable()) {
                return false;
            }

            return Cache::forget($key);
        } catch (\Throwable $e) {
            Log::warning('Cache forget failed', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function isRedisAvailable(): bool
    {
        try {
            $driver = config('cache.default');
            if ($driver !== 'redis') {
                return true;
            }

            Cache::getStore()->getRedis()->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
