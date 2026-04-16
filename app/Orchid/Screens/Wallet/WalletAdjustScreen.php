<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Wallet;

use App\Enums\WalletOperationType;
use App\Models\Agent;
use App\Models\Merchant;
use App\Models\Provider;
use App\Services\Wallet\AgentWalletService;
use App\Services\Wallet\MerchantWalletService;
use App\Services\Wallet\ProviderWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WalletAdjustScreen extends Screen
{
    public $permission = 'platform.wallets';

    public function name(): ?string
    {
        return __('Manual Adjustment');
    }

    public function description(): ?string
    {
        return __('Manually credit or debit wallets');
    }

    public function query(): iterable
    {
        return [];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $merchantOptions = Merchant::pluck('name', 'id')->all();
        $agentOptions = Agent::pluck('name', 'id')->all();
        $providerOptions = Provider::pluck('name', 'id')->all();

        return [
            Layout::rows([
                Select::make('entity_type')
                    ->title(__('Entity Type'))
                    ->options([
                        'merchant' => __('Merchant'),
                        'agent' => __('Agent'),
                        'provider' => __('Provider'),
                    ])
                    ->required(),

                Select::make('merchant_id')
                    ->title(__('Merchant'))
                    ->options($merchantOptions)
                    ->empty(__('Select Entity')),

                Select::make('agent_id')
                    ->title(__('Agent'))
                    ->options($agentOptions)
                    ->empty(__('Select Entity')),

                Select::make('provider_id')
                    ->title(__('Provider'))
                    ->options($providerOptions)
                    ->empty(__('Select Entity')),

                Select::make('operation')
                    ->title(__('Operation'))
                    ->options([
                        'manual_credit' => __('Manual Credit'),
                        'manual_debit' => __('Manual Debit'),
                    ])
                    ->required(),

                Input::make('amount')
                    ->title(__('Adjustment Amount'))
                    ->type('number')
                    ->step('0.000001')
                    ->required(),

                TextArea::make('remark')
                    ->title(__('Adjustment Remark'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(Request $request): RedirectResponse
    {
        $request->validate([
            'entity_type' => 'required|in:merchant,agent,provider',
            'operation' => 'required|in:manual_credit,manual_debit',
            'amount' => 'required|numeric|gt:0',
            'remark' => 'nullable|string|max:500',
        ]);

        $entityType = $request->input('entity_type');
        $operation = $request->input('operation');
        $amount = $request->input('amount');
        $remark = $request->input('remark');

        $walletOpType = $operation === 'manual_credit'
            ? WalletOperationType::MANUAL_CREDIT
            : WalletOperationType::MANUAL_DEBIT;

        $method = $operation === 'manual_credit' ? 'credit' : 'debit';

        switch ($entityType) {
            case 'merchant':
                $request->validate(['merchant_id' => 'required|exists:merchants,id']);
                $entityId = (int) $request->input('merchant_id');
                app(MerchantWalletService::class)->$method($entityId, $amount, $walletOpType, null, $remark);
                break;

            case 'agent':
                $request->validate(['agent_id' => 'required|exists:agents,id']);
                $entityId = (int) $request->input('agent_id');
                app(AgentWalletService::class)->$method($entityId, $amount, $walletOpType, null, $remark);
                break;

            case 'provider':
                $request->validate(['provider_id' => 'required|exists:providers,id']);
                $entityId = (int) $request->input('provider_id');
                app(ProviderWalletService::class)->$method($entityId, $amount, $walletOpType, null, $remark);
                break;
        }

        Toast::info(__('Adjustment saved successfully.'));

        return redirect()->route('platform.wallets.adjust');
    }
}
