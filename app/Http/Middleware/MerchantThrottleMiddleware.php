<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class MerchantThrottleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $maxAttempts = (int) config('fluxpay.merchant_api_rate_limit_per_minute', 60);
        $key = $this->resolveKey($request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'code' => 1007,
                'message' => 'Too many requests',
                'data' => null,
                'timestamp' => time(),
            ], Response::HTTP_TOO_MANY_REQUESTS)
                ->header('Retry-After', (string) RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }

    private function resolveKey(Request $request): string
    {
        $merchantNo = $request->input('merchantNo');

        return $merchantNo
            ? 'merchant-api:no:'.$merchantNo
            : 'merchant-api:ip:'.$request->ip();
    }
}
