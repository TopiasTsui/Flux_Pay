<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    private const SESSION_KEY = 'two_factor_passed';

    public function __construct(private readonly TwoFactorService $twoFactor) {}

    public function challenge(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user || ! $this->twoFactor->isEnabled($user)) {
            return redirect()->route('platform.main');
        }

        return view('platform.2fa.challenge', ['error' => null]);
    }

    public function verify(Request $request): RedirectResponse|View
    {
        $request->validate(['code' => 'required|string']);

        $user = Auth::user();
        if (! $user || ! $this->twoFactor->isEnabled($user)) {
            return redirect()->route('platform.main');
        }

        if (! $this->twoFactor->verify($user, (string) $request->input('code'))) {
            return back()->with('error', 'Invalid verification code.');
        }

        $request->session()->put(self::SESSION_KEY, true);

        return redirect()->intended(route('platform.main'));
    }
}
