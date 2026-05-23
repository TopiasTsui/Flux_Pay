<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\AdminUserIpWhitelist;
use App\Services\Security\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Action;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Group;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Label;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserSecurityScreen extends Screen
{
    private string $state = 'disabled';

    private ?string $pendingSecret = null;

    private ?string $otpauthUrl = null;

    public function query(TwoFactorService $service): iterable
    {
        $user = Auth::user();

        if ($service->isEnabled($user)) {
            $this->state = 'enabled';
        } elseif ($service->isPending($user)) {
            $this->state = 'pending';
            $this->pendingSecret = $user->two_factor_secret;
            $this->otpauthUrl = $service->otpauthUrl($user, $this->pendingSecret);
        }

        return [
            'user' => $user,
            'ip_whitelist' => AdminUserIpWhitelist::query()
                ->where('admin_user_id', $user->id)
                ->orderByDesc('id')
                ->get(),
            'pending_secret' => $this->pendingSecret,
            'otpauth_url' => $this->otpauthUrl,
            'confirmed_at' => $user->two_factor_confirmed_at,
        ];
    }

    public function name(): ?string
    {
        return __('Security');
    }

    public function description(): ?string
    {
        return __('Two-factor authentication and IP whitelist for your account.');
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::block($this->twoFactorLayout())
                ->title(__('Two-Factor Authentication'))
                ->description($this->twoFactorDescription()),

            Layout::block($this->ipWhitelistLayout())
                ->title(__('IP Whitelist'))
                ->description(__('Restrict admin logins to specific IPs. Leave empty to disable the restriction.')),
        ];
    }

    private function twoFactorDescription(): string
    {
        return match ($this->state) {
            'enabled' => __('2FA is active. Every login requires a code from your authenticator app.'),
            'pending' => __('Scan the secret in your authenticator app, then enter a 6-digit code to confirm.'),
            default => __('Enable 2FA to require a one-time code in addition to your password.'),
        };
    }

    private function twoFactorLayout(): array
    {
        return match ($this->state) {
            'enabled' => [
                Layout::rows([
                    Label::make('confirmed_at')
                        ->title(__('Enabled since'))
                        ->popover(__('When you confirmed your authenticator app.')),
                ]),
                Layout::rows([
                    Button::make(__('Disable 2FA'))
                        ->icon('bs.shield-slash')
                        ->type(Color::DANGER)
                        ->confirm(__('Disable 2FA? You will only be required to enter your password to log in.'))
                        ->method('disableTwoFactor'),
                ]),
            ],
            'pending' => [
                Layout::rows([
                    Label::make('pending_secret')->title(__('Secret (base32)')),
                    Label::make('otpauth_url')->title(__('otpauth URL')),
                    Input::make('code')
                        ->type('text')
                        ->required()
                        ->autocomplete('one-time-code')
                        ->placeholder('123456')
                        ->title(__('Code from authenticator app')),
                ]),
                Layout::rows([
                    Group::make([
                        Button::make(__('Confirm and enable'))
                            ->icon('bs.check-circle')
                            ->type(Color::PRIMARY)
                            ->method('confirmTwoFactor'),
                        Button::make(__('Cancel'))
                            ->icon('bs.x-circle')
                            ->type(Color::SECONDARY)
                            ->method('cancelTwoFactor'),
                    ]),
                ]),
            ],
            default => [
                Layout::rows([
                    Button::make(__('Generate secret'))
                        ->icon('bs.shield-lock')
                        ->type(Color::PRIMARY)
                        ->method('beginTwoFactor'),
                ]),
            ],
        };
    }

    private function ipWhitelistLayout(): array
    {
        return [
            Layout::table('ip_whitelist', [
                TD::make('ip_address', __('IP Address')),
                TD::make('remark', __('Remark'))->render(fn ($row) => e($row->remark ?? '—')),
                TD::make('status', __('Status'))->render(fn ($row) => $row->status ? __('Active') : __('Inactive')),
                TD::make('created_at', __('Added'))->render(fn ($row) => $row->created_at?->format('Y-m-d H:i')),
                TD::make(__('Actions'))
                    ->alignRight()
                    ->render(fn ($row) => Button::make(__('Remove'))
                        ->icon('bs.trash')
                        ->type(Color::DANGER)
                        ->confirm(__('Remove this IP from your whitelist?'))
                        ->method('removeIp', ['id' => $row->id])),
            ]),
            Layout::rows([
                Group::make([
                    Input::make('new_ip')
                        ->type('text')
                        ->placeholder('203.0.113.42')
                        ->title(__('IP Address')),
                    Input::make('new_remark')
                        ->type('text')
                        ->placeholder(__('e.g. Office'))
                        ->title(__('Remark')),
                ]),
                Button::make(__('Add IP'))
                    ->icon('bs.plus-circle')
                    ->type(Color::PRIMARY)
                    ->method('addIp'),
            ]),
        ];
    }

    public function beginTwoFactor(TwoFactorService $service): RedirectResponse
    {
        $service->beginEnrollment(Auth::user());
        Toast::info(__('Secret generated. Scan it in your authenticator app and confirm with a code.'));

        return redirect()->route('platform.profile.security');
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $service): RedirectResponse
    {
        $request->validate(['code' => 'required|string']);

        if ($service->confirmEnrollment(Auth::user()->fresh(), (string) $request->input('code'))) {
            $request->session()->put('two_factor_passed', true);
            Toast::success(__('Two-factor authentication enabled.'));
        } else {
            Toast::error(__('Invalid code. Please try again.'));
        }

        return redirect()->route('platform.profile.security');
    }

    public function cancelTwoFactor(TwoFactorService $service): RedirectResponse
    {
        $service->disable(Auth::user());
        Toast::info(__('Enrollment cancelled.'));

        return redirect()->route('platform.profile.security');
    }

    public function disableTwoFactor(TwoFactorService $service): RedirectResponse
    {
        $service->disable(Auth::user());
        Toast::info(__('Two-factor authentication disabled.'));

        return redirect()->route('platform.profile.security');
    }

    public function addIp(Request $request): RedirectResponse
    {
        $request->validate([
            'new_ip' => 'required|ip',
            'new_remark' => 'nullable|string|max:255',
        ]);

        AdminUserIpWhitelist::create([
            'admin_user_id' => Auth::id(),
            'ip_address' => $request->input('new_ip'),
            'remark' => $request->input('new_remark'),
            'status' => 1,
        ]);

        Toast::success(__('IP added to whitelist.'));

        return redirect()->route('platform.profile.security');
    }

    public function removeIp(Request $request): RedirectResponse
    {
        $deleted = AdminUserIpWhitelist::where('id', $request->input('id'))
            ->where('admin_user_id', Auth::id())
            ->delete();

        if ($deleted) {
            Toast::info(__('IP removed.'));
        }

        return redirect()->route('platform.profile.security');
    }
}
