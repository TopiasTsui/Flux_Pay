<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\EntityStatus;
use App\Models\Provider;
use App\Services\Security\IpWhitelistService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProviderCallbackMiddleware
{
    public function __construct(
        private readonly IpWhitelistService $ipWhitelistService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $vendor = $request->route('vendor');

        if (! $vendor) {
            return response('fail', 400);
        }

        $provider = Provider::where('provider_no', $vendor)->first();

        if (! $provider) {
            return response('fail', 400);
        }

        if ($provider->status !== EntityStatus::ACTIVE) {
            return response('fail', 403);
        }

        // Validate callback IP
        $clientIp = $request->ip();
        $callbackIps = $provider->call_back_ips ?? '';

        if (! $this->ipWhitelistService->isAllowed($clientIp, $callbackIps)) {
            return response('fail', 403);
        }

        $request->merge(['_provider' => $provider]);

        return $next($request);
    }
}
