<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use App\Enums\PaymentDirection;
use App\Models\PaymentType;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProviderPaymentTypeEditScreen extends Screen
{
    public $permission = 'platform.payment-config';

    public ?ProviderPaymentType $channel = null;

    public function name(): ?string
    {
        return $this->channel?->exists ? __('Edit Provider Channel') : __('Create Provider Channel');
    }

    public function query(ProviderPaymentType $channel): iterable
    {
        return [
            'channel' => $channel,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))
                ->icon('bs.arrow-left')
                ->route('platform.provider-payment-types'),

            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Delete this channel?'))
                ->canSee($this->channel?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        $feeTypeOptions = collect(FeeType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
        $directionOptions = collect(PaymentDirection::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();

        return [
            Layout::rows([
                Relation::make('channel.provider_id')
                    ->title(__('Provider'))
                    ->fromModel(Provider::class, 'name')
                    ->required(),

                Relation::make('channel.payment_type_id')
                    ->title(__('Payment Type'))
                    ->fromModel(PaymentType::class, 'name')
                    ->required(),

                Select::make('channel.type')
                    ->title(__('Direction'))
                    ->options($directionOptions)
                    ->required(),

                Input::make('channel.alias')
                    ->title(__('Alias')),

                Select::make('channel.status')
                    ->title(__('Status'))
                    ->options(EntityStatus::options())
                    ->required(),

                Input::make('channel.weight')
                    ->title(__('Weight'))
                    ->type('number')
                    ->value(0),

                Input::make('channel.single_min_amount')
                    ->title(__('Min Amount'))
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.single_max_amount')
                    ->title(__('Max Amount'))
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.daily_amount_limit')
                    ->title(__('Daily Amount Limit'))
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.daily_count_limit')
                    ->title(__('Daily Count Limit'))
                    ->type('number'),

                Select::make('channel.deposit_fee_type')
                    ->title(__('Deposit Fee Type'))
                    ->options($feeTypeOptions)
                    ->empty(__('None')),

                Input::make('channel.deposit_fee')
                    ->title(__('Deposit Fee'))
                    ->type('number')
                    ->step('0.000001'),

                Select::make('channel.withdraw_fee_type')
                    ->title(__('Withdraw Fee Type'))
                    ->options($feeTypeOptions)
                    ->empty(__('None')),

                Input::make('channel.withdraw_fee')
                    ->title(__('Withdraw Fee'))
                    ->type('number')
                    ->step('0.000001'),

                Input::make('channel.agent_fee')
                    ->title(__('Agent Fee'))
                    ->type('number')
                    ->step('0.000001'),
            ]),
        ];
    }

    public function save(ProviderPaymentType $channel, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'channel.provider_id' => 'required|exists:providers,id',
            'channel.payment_type_id' => 'required|exists:payment_types,id',
            'channel.type' => ['required', Rule::enum(PaymentDirection::class)],
            'channel.alias' => 'nullable|string|max:64',
            'channel.status' => ['required', Rule::enum(EntityStatus::class)],
            'channel.weight' => 'nullable|integer|min:0',
            'channel.single_min_amount' => 'nullable|numeric|min:0',
            'channel.single_max_amount' => 'nullable|numeric|min:0',
            'channel.daily_amount_limit' => 'nullable|numeric|min:0',
            'channel.daily_count_limit' => 'nullable|integer|min:0',
            'channel.deposit_fee_type' => 'nullable',
            'channel.deposit_fee' => 'nullable|numeric|min:0',
            'channel.withdraw_fee_type' => 'nullable',
            'channel.withdraw_fee' => 'nullable|numeric|min:0',
            'channel.agent_fee' => 'nullable|numeric|min:0',
        ]);

        $channel->fill($data['channel'])->save();

        Toast::info(__('Saved successfully.'));

        return redirect()->route('platform.provider-payment-types');
    }

    public function remove(ProviderPaymentType $channel): RedirectResponse
    {
        $channel->delete();
        Toast::info(__('Provider channel deleted.'));

        return redirect()->route('platform.provider-payment-types');
    }
}
