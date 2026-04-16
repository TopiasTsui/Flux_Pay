<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Merchant;

use App\Enums\Currency;
use App\Enums\EntityStatus;
use App\Enums\WalletOperationType;
use App\Models\Agent;
use App\Models\Merchant;
use App\Services\Wallet\MerchantWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Platform\Models\Role;
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
        return ['merchant' => $merchant];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))->icon('bs.arrow-left')->route('platform.merchants'),
            Button::make(__('Save'))->icon('bs.check')->method('save'),
            Button::make(__('Delete'))->icon('bs.trash')->method('remove')
                ->confirm(__('Are you sure you want to delete this merchant?'))
                ->canSee($this->merchant?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        $layouts = [
            Layout::rows([
                Input::make('merchant.code')->title(__('Code'))->required(),
                Input::make('merchant.name')->title(__('Name'))->required(),
                Relation::make('merchant.agent_id')->title(__('Agent'))->fromModel(Agent::class, 'name')->required(),
                Select::make('merchant.currency_code')->title(__('Currency'))->options(Currency::options())->required(),
                Select::make('merchant.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                TextArea::make('merchant.white_ips')->title(__('White IPs (JSON array)'))->help(__('e.g. ["1.2.3.4", "5.6.7.8"]'))->rows(3),
                Input::make('merchant.md5key')->title(__('MD5 Key'))->readonly()->canSee($this->merchant?->exists ?? false),
            ]),
        ];

        if ($this->merchant?->exists) {
            $layouts[] = Layout::rows([
                Input::make('_balance_info')->title(__('Current Balance'))->readonly()->disabled()
                    ->value(__('Available') . ': ' . $this->merchant->available_balance . '  |  ' . __('Hold') . ': ' . $this->merchant->hold_balance . '  |  ' . __('Total') . ': ' . $this->merchant->total_balance),
                Select::make('adjust.operation')->title(__('Operation'))
                    ->options(['credit' => __('Manual Credit'), 'debit' => __('Manual Debit')])->empty(__('-- Select --')),
                Input::make('adjust.amount')->title(__('Adjustment Amount'))->type('number')->step('0.01'),
                TextArea::make('adjust.remark')->title(__('Adjustment Remark'))->rows(2),
                Button::make(__('Submit Adjustment'))->icon('bs.wallet2')->method('adjust')
                    ->confirm(__('Are you sure you want to adjust the wallet?')),
            ])->title(__('Wallet Adjustment'));
        }

        return $layouts;
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
        if (!empty($merchantData['white_ips'])) {
            $decoded = json_decode($merchantData['white_ips'], true);
            $merchantData['white_ips'] = is_array($decoded) ? $decoded : [];
        } else {
            $merchantData['white_ips'] = [];
        }
        $isNew = !$merchant->exists;
        if ($isNew) {
            $merchantData['md5key'] = Str::random(32);
        }
        $merchant->fill($merchantData)->save();
        if ($isNew) {
            $user = \App\Models\User::create([
                'username' => $merchantData['code'],
                'name' => $merchantData['name'],
                'password' => Hash::make($merchantData['code']),
                'merchant_id' => $merchant->id,
                'is_active' => true,
            ]);
            $role = Role::where('slug', 'merchant')->first();
            if ($role) $user->addRole($role);
        }
        Toast::info(__('Saved successfully.'));
        return redirect()->route('platform.merchants');
    }

    public function adjust(Merchant $merchant, Request $request, MerchantWalletService $walletService): RedirectResponse
    {
        $request->validate([
            'adjust.operation' => 'required|in:credit,debit',
            'adjust.amount' => 'required|numeric|min:0.01',
            'adjust.remark' => 'nullable|string|max:255',
        ]);
        $op = $request->input('adjust.operation');
        $amount = (string) $request->input('adjust.amount');
        $remark = $request->input('adjust.remark', '');
        $type = $op === 'credit' ? WalletOperationType::MANUAL_CREDIT : WalletOperationType::MANUAL_DEBIT;
        if ($op === 'credit') {
            $walletService->credit($merchant->id, $amount, $type, null, $remark);
        } else {
            $walletService->debit($merchant->id, $amount, $type, null, $remark);
        }
        Toast::info(__('Adjustment saved successfully.'));
        return redirect()->route('platform.merchants.edit', $merchant);
    }

    public function remove(Merchant $merchant): RedirectResponse
    {
        $merchant->delete();
        Toast::info(__('Merchant deleted.'));
        return redirect()->route('platform.merchants');
    }
}
