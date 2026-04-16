<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\EntityStatus;
use App\Models\Merchant;
use App\Services\Security\IpWhitelistService;
use App\Services\Security\SignatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MerchantAuthMiddleware
{
    public function __construct(
        private readonly IpWhitelistService $ipWhitelistService,
        private readonly SignatureService $signatureService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $merchantNo = $request->input('merchantNo');

        if (! $merchantNo) {
            return response()->json([
                'code' => 1001,
                'message' => 'Missing merchantNo',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        $merchant = Merchant::where('code', $merchantNo)->first();

        if (! $merchant) {
            return response()->json([
                'code' => 1002,
                'message' => 'Merchant not found',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        if ($merchant->status !== EntityStatus::ACTIVE) {
            return response()->json([
                'code' => 1003,
                'message' => 'Merchant is not active',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        // Validate IP whitelist
        $clientIp = $request->ip();
        $whiteIps = $merchant->white_ips ?? [];

        if (! $this->ipWhitelistService->isAllowed($clientIp, $whiteIps)) {
            return response()->json([
                'code' => 1004,
                'message' => 'IP address not allowed',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        // Validate signature
        $signature = $request->input('signature');
        if (! $signature) {
            return response()->json([
                'code' => 1005,
                'message' => 'Missing signature',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        $params = $request->except(['signature', '_merchant']);
        if (! $this->signatureService->verify($params, $merchant->md5key, $signature)) {
            return response()->json([
                'code' => 1006,
                'message' => 'Invalid signature',
                'data' => null,
                'timestamp' => time(),
            ], 200);
        }

        $request->merge(['_merchant' => $merchant]);

        return $next($request);
    }
}
