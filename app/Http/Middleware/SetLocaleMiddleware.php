<?php

namespace App\Http\Middleware;

use App\Models\Locale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SetLocaleMiddleware
{
    private const ACTIVE_LOCALES_CACHE = 'i18n:active_locales';

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && !empty($user->locale) && $this->isActive($user->locale)) {
            App::setLocale($user->locale);
        }

        return $next($request);
    }

    private function isActive(string $code): bool
    {
        $active = Cache::remember(self::ACTIVE_LOCALES_CACHE, 300, function () {
            try {
                return Locale::activeCodes();
            } catch (\Throwable $e) {
                return [];
            }
        });

        // When the locales table is unavailable, don't block the user's choice.
        return $active === [] || in_array($code, $active, true);
    }
}
