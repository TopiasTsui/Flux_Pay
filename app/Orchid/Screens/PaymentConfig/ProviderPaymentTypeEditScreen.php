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
use Orchid\Screen\Actions\Button;
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
        return $this->channel?->exists ? 'Edit Provider Channel' : 'Create Provider Channel';
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
            Button::make('Save')
                ->icon('bs.check')
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash')
                ->method('remove')
                ->confirm('Delete this channel?')
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
                    ->title('Provider')
                    ->fromModel(Provider::class, 'name')
                    ->required(),

                Relation::make('channel.payment_type_id')
                    ->title('Payment Type')
                    ->fromModel(PaymentType::class, 'name')
                    ->required(),

                Select::make('channel.type')
                    ->title('Direction')
                    ->options($directionOptions)
                    ->required(),

                Input::make('channel.alias')
                    ->title('Alias'),

                Select::make('channel.status')
                    ->title('Status')
                    ->options(EntityStatus::options())
                    ->required(),

                Input::make('channel.weight')
                    ->title('Weight')
                    ->type('number')
                    ->value(0),

                Input::make('channel.single_min_amount')
                    ->title('Min Amount')
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.single_max_amount')
                    ->title('Max Amount')
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.daily_amount_limit')
                    ->title('Daily Amount Limit')
                    ->type('number')
                    ->step('0.01'),

                Input::make('channel.daily_count_limit')
                    ->title('Daily Count Limit')
                    ->type('number'),

                Select::make('channel.deposit_fee_type')
                    ->title('Deposit Fee Type')
                    ->options($feeTypeOptions)
                    ->empty('None'),

                Input::make('channel.deposit_fee')
                    ->title('Deposit Fee')
                    ->type('number')
                    ->step('0.000001'),

                Select::make('channel.withdraw_fee_type')
                    ->title('Withdraw Fee Type')
                    ->options($feeTypeOptions)
                    ->empty('None'),

                Input::make('channel.withdraw_fee')
                    ->title('Withdraw Fee')
                    ->type('number')
                    ->step('0.000001'),

                Input::make('channel.agent_fee')
                    ->title('Agent Fee')
                    ->type('number')
                    ->step('0.000001'),
            ]),
        ];
    }

    public function save(ProviderPaymentType $channel, Request $request): void
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

        Toast::info('Provider channel saved.');
    }

    public function remove(ProviderPaymentType $channel): void
    {
        $channel->delete();
        Toast::info('Provider channel deleted.');
    }
}
