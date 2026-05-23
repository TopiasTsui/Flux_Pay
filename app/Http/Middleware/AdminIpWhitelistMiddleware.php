<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AdminUserIpWhitelist;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-user IP whitelist for the admin platform.
 *
 * Behavior:
 *   - User has zero active rows in admin_user_ip_whitelists → opt-out, pass through.
 *   - User has one or more active rows → client IP MUST match one of them, else 403.
 *
 * IP comparison is exact-string for now (no CIDR). Extend with App\Services\Security\IpWhitelistService
 * if range/CIDR support is needed.
 */
class AdminIpWhitelistMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        $allowed = AdminUserIpWhitelist::query()
            ->where('admin_user_id', $user->id)
            ->where('status', 1)
            ->pluck('ip_address')
            ->all();

        if (empty($allowed)) {
            return $next($request);
        }

        if (! in_array($request->ip(), $allowed, true)) {
            abort(403, 'Your IP address is not on the admin whitelist.');
        }

        return $next($request);
    }
}
