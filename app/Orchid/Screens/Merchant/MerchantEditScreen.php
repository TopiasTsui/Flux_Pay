<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Merchant;

use App\Enums\Currency;
use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MerchantEditScreen extends Screen
{
    public $permission = 'platform.merchants';

    public ?Merchant $merchant = null;

    public function name(): ?string
    {
        return $this->merchant?->exists ? __('Edit Merchant') : __('Create Merchant');
    }

    public function query(Merchant $merchant): iterable
    {
        if ($merchant->exists) {
            $merchant->white_ips = is_array($merchant->white_ips)
                ? json_encode($merchant->white_ips, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $merchant->white_ips;
        }

        return [
            'merchant' => $merchant,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))
                ->icon('bs.arrow-left')
                ->route('platform.merchants'),

            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this merchant?'))
                ->canSee($this->merchant?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('merchant.code')
                    ->title(__('Code'))
                    ->required(),

                Input::make('merchant.name')
                    ->title(__('Name'))
                    ->required(),

                Relation::make('merchant.agent_id')
                    ->title(__('Agent'))
                    ->fromModel(Agent::class, 'name')
                    ->required(),

                Select::make('merchant.currency_code')
                    ->title(__('Currency'))
                    ->options(Currency::options())
                    ->required(),

                Select::make('merchant.status')
                    ->title(__('Status'))
                    ->options(EntityStatus::options())
                    ->required(),

                TextArea::make('merchant.white_ips')
                    ->title(__('White IPs (JSON array)'))
                    ->help(__('e.g. ["1.2.3.4", "5.6.7.8"]'))
                    ->rows(3),

                Input::make('merchant.md5key')
                    ->title(__('MD5 Key'))
                    ->readonly()
                    ->canSee($this->merchant?->exists ?? false),
            ]),
        ];
    }

    public function save(Merchant $merchant, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'merchant.code' => ['required', 'string', 'max:64', Rule::unique('merchants', 'code')->ignore($merchant->id)],
            'merchant.name' => 'required|string|max:255',
            'merchant.agent_id' => 'required|exists:agents,id',
            'merchant.currency_code' => ['required', Rule::enum(Currency::class)],
            'merchant.status' => ['required', Rule::enum(EntityStatus::class)],
            'merchant.white_ips' => 'nullable|string',
        ]);

        $merchantData = $data['merchant'];

        // Parse white_ips JSON
        if (!empty($merchantData['white_ips'])) {
            $decoded = json_decode($merchantData['white_ips'], true);
            $merchantData['white_ips'] = is_array($decoded) ? $decoded : [];
        } else {
            $merchantData['white_ips'] = [];
        }

        // Auto-generate md5key on create
        $isNew = !$merchant->exists;
        if ($isNew) {
            $merchantData['md5key'] = Str::random(32);
        }

        $merchant->fill($merchantData)->save();

        // Create backend user account for new merchant
        if ($isNew) {
            $user = \App\Models\User::create([
                'username' => $merchantData['code'],
                'name' => $merchantData['name'],
                'password' => \Illuminate\Support\Facades\Hash::make($merchantData['code']),
                'merchant_id' => $merchant->id,
                'is_active' => true,
            ]);
            $merchantRole = \Orchid\Platform\Models\Role::where('slug', 'merchant')->first();
            if ($merchantRole) {
                $user->addRole($merchantRole);
            }
        }

        Toast::info(__('Saved successfully.'));

        return redirect()->route('platform.merchants');
    }

    public function remove(Merchant $merchant): RedirectResponse
    {
        $merchant->delete();
        Toast::info(__('Merchant deleted.'));

        return redirect()->route('platform.merchants');
    }
}
