<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use App\Models\Merchant;
use App\Models\MerchantPaymentType;
use App\Models\PaymentType;
use Illuminate\Http\Request;
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

class MerchantPaymentTypeEditScreen extends Screen
{
    public $permission = 'platform.payment-config';

    public ?MerchantPaymentType $config = null;

    public function name(): ?string
    {
        return $this->config?->exists ? __('Edit Merchant Payment Config') : __('Create Merchant Payment Config');
    }

    public function query(MerchantPaymentType $config): iterable
    {
        if ($config->exists) {
            foreach (['deposit_agents_fee', 'withdraw_agents_fee'] as $field) {
                $config->$field = is_array($config->$field)
                    ? json_encode($config->$field, JSON_PRETTY_PRINT)
                    : $config->$field;
            }
        }

        return [
            'config' => $config,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))
                ->icon('bs.arrow-left')
                ->route('platform.merchant-payment-types'),

            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Delete this config?'))
                ->canSee($this->config?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        $feeTypeOptions = collect(FeeType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();

        return [
            Layout::rows([
                Relation::make('config.merchant_id')
                    ->title(__('Merchant'))
                    ->fromModel(Merchant::class, 'name')
                    ->required(),

                Relation::make('config.payment_type_id')
                    ->title(__('Payment Type'))
                    ->fromModel(PaymentType::class, 'name')
                    ->required(),

                Select::make('config.status')
                    ->title(__('Status'))
                    ->options(EntityStatus::options())
                    ->required(),

                Select::make('config.deposit_fee_type')
                    ->title(__('Deposit Fee Type'))
                    ->options($feeTypeOptions)
                    ->empty(__('None')),

                Input::make('config.deposit_fee')
                    ->title(__('Deposit Fee'))
                    ->type('number')
                    ->step('0.000001'),

                Select::make('config.withdraw_fee_type')
                    ->title(__('Withdraw Fee Type'))
                    ->options($feeTypeOptions)
                    ->empty(__('None')),

                Input::make('config.withdraw_fee')
                    ->title(__('Withdraw Fee'))
                    ->type('number')
                    ->step('0.000001'),

                TextArea::make('config.deposit_agents_fee')
                    ->title(__('Deposit Agents Fee (JSON)'))
                    ->help(__('e.g. {"agent_1": "0.5", "agent_2": "0.3"}'))
                    ->rows(3),

                TextArea::make('config.withdraw_agents_fee')
                    ->title(__('Withdraw Agents Fee (JSON)'))
                    ->help(__('e.g. {"agent_1": "0.5", "agent_2": "0.3"}'))
                    ->rows(3),
            ]),
        ];
    }

    public function save(MerchantPaymentType $config, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'config.merchant_id' => 'required|exists:merchants,id',
            'config.payment_type_id' => 'required|exists:payment_types,id',
            'config.status' => ['required', Rule::enum(EntityStatus::class)],
            'config.deposit_fee_type' => 'nullable',
            'config.deposit_fee' => 'nullable|numeric|min:0',
            'config.withdraw_fee_type' => 'nullable',
            'config.withdraw_fee' => 'nullable|numeric|min:0',
            'config.deposit_agents_fee' => 'nullable|string',
            'config.withdraw_agents_fee' => 'nullable|string',
        ]);

        $configData = $data['config'];

        // Parse JSON fields
        foreach (['deposit_agents_fee', 'withdraw_agents_fee'] as $field) {
            if (!empty($configData[$field])) {
                $decoded = json_decode($configData[$field], true);
                $configData[$field] = is_array($decoded) ? $decoded : [];
            }
        }

        $config->fill($configData)->save();

        Toast::info(__('Saved successfully.'));

        return redirect()->route('platform.merchant-payment-types');
    }

    public function remove(MerchantPaymentType $config): RedirectResponse
    {
        $config->delete();
        Toast::info(__('Config deleted.'));

        return redirect()->route('platform.merchant-payment-types');
    }
}
