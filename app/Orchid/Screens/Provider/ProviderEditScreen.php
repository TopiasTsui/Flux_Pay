<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Provider;

use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Models\Provider;
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

class ProviderEditScreen extends Screen
{
    public $permission = 'platform.providers';

    public ?Provider $provider = null;

    public function name(): ?string
    {
        return $this->provider?->exists ? __('Edit Provider') : __('Create Provider');
    }

    public function query(Provider $provider): iterable
    {
        if ($provider->exists) {
            $provider->vendor_meta = is_array($provider->vendor_meta)
                ? json_encode($provider->vendor_meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $provider->vendor_meta;
        }

        return [
            'provider' => $provider,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))
                ->icon('bs.arrow-left')
                ->route('platform.providers'),

            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this provider?'))
                ->canSee($this->provider?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('provider.name')
                    ->title(__('Name'))
                    ->required(),

                Input::make('provider.vendor_id')
                    ->title(__('Vendor ID'))
                    ->required(),

                Input::make('provider.provider_no')
                    ->title(__('Provider No')),

                Relation::make('provider.agent_id')
                    ->title(__('Agent'))
                    ->fromModel(Agent::class, 'name'),

                Select::make('provider.status')
                    ->title(__('Status'))
                    ->options(EntityStatus::options())
                    ->required(),

                TextArea::make('provider.vendor_meta')
                    ->title(__('Vendor Meta (JSON)'))
                    ->help(__('Provider-specific configuration in JSON format'))
                    ->rows(6),

                TextArea::make('provider.call_back_ips')
                    ->title(__('Callback IPs'))
                    ->help(__('Comma-separated IP addresses'))
                    ->rows(2),
            ]),
        ];
    }

    public function save(Provider $provider, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider.name' => 'required|string|max:255',
            'provider.vendor_id' => 'required|string|max:64',
            'provider.provider_no' => 'nullable|string|max:64',
            'provider.agent_id' => 'nullable|exists:agents,id',
            'provider.status' => ['required', Rule::enum(EntityStatus::class)],
            'provider.vendor_meta' => 'nullable|string',
            'provider.call_back_ips' => 'nullable|string',
        ]);

        $providerData = $data['provider'];

        // Parse vendor_meta JSON
        if (!empty($providerData['vendor_meta'])) {
            $decoded = json_decode($providerData['vendor_meta'], true);
            $providerData['vendor_meta'] = is_array($decoded) ? $decoded : [];
        }

        $provider->fill($providerData)->save();

        Toast::info(__('Saved successfully.'));

        return redirect()->route('platform.providers');
    }

    public function remove(Provider $provider): RedirectResponse
    {
        $provider->delete();
        Toast::info(__('Provider deleted.'));

        return redirect()->route('platform.providers');
    }
}
