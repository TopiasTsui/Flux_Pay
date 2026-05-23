<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces 2FA challenge after Orchid login.
 *
 *   - User without 2FA enrolled → pass through (opt-in).
 *   - User with 2FA, session 'two_factor_passed' already set → pass through.
 *   - Otherwise → redirect to the challenge page. Lets the challenge route itself
 *     and logout through, so the user isn't trapped in a redirect loop.
 */
class RequireTwoFactorMiddleware
{
    private const SESSION_KEY = 'two_factor_passed';

    private const EXEMPT_ROUTES = [
        'platform.2fa.challenge',
        'platform.2fa.verify',
        'platform.logout',
    ];

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user || ! $this->twoFactor->isEnabled($user)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::EXEMPT_ROUTES, true)) {
            return $next($request);
        }

        if ($request->session()->get(self::SESSION_KEY) === true) {
            return $next($request);
        }

        return redirect()->route('platform.2fa.challenge');
    }
}
