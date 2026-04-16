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
use Orchid\Screen\Actions\Button;
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
        return $this->merchant?->exists ? 'Edit Merchant' : 'Create Merchant';
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
            Button::make('Save')
                ->icon('bs.check')
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash')
                ->method('remove')
                ->confirm('Are you sure you want to delete this merchant?')
                ->canSee($this->merchant?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('merchant.code')
                    ->title('Code')
                    ->required(),

                Input::make('merchant.name')
                    ->title('Name')
                    ->required(),

                Relation::make('merchant.agent_id')
                    ->title('Agent')
                    ->fromModel(Agent::class, 'name')
                    ->required(),

                Select::make('merchant.currency_code')
                    ->title('Currency')
                    ->options(Currency::options())
                    ->required(),

                Select::make('merchant.status')
                    ->title('Status')
                    ->options(EntityStatus::options())
                    ->required(),

                TextArea::make('merchant.white_ips')
                    ->title('White IPs (JSON array)')
                    ->help('e.g. ["1.2.3.4", "5.6.7.8"]')
                    ->rows(3),

                Input::make('merchant.md5key')
                    ->title('MD5 Key')
                    ->readonly()
                    ->canSee($this->merchant?->exists ?? false),
            ]),
        ];
    }

    public function save(Merchant $merchant, Request $request): void
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
        if (!$merchant->exists) {
            $merchantData['md5key'] = Str::random(32);
        }

        $merchant->fill($merchantData)->save();

        Toast::info('Merchant saved successfully.');
    }

    public function remove(Merchant $merchant): void
    {
        $merchant->delete();
        Toast::info('Merchant deleted.');
    }
}
