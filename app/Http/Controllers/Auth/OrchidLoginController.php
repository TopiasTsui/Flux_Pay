<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Platform\Http\Controllers\LoginController as BaseLoginController;

class OrchidLoginController extends BaseLoginController
{
    public function login(Request $request, CookieJar $cookieJar): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (!$user || !$user->is_active) {
            return back()->withErrors(['username' => __('Account is disabled or not found.')]);
        }

        if (Auth::attempt([
            'username' => $request->input('username'),
            'password' => $request->input('password'),
        ], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route(config('platform.index')));
        }

        return back()->withErrors(['username' => __('Invalid credentials.')]);
    }
}
