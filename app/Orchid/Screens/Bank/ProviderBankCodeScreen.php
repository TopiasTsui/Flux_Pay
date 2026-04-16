<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Bank;

use App\Enums\EntityStatus;
use App\Models\ProviderBankCode;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProviderBankCodeScreen extends Screen
{
    public $permission = 'platform.banks';

    public function name(): ?string
    {
        return __('Provider Bank Code Mappings');
    }

    public function description(): ?string
    {
        return __('Map system bank codes to provider-specific codes');
    }

    public function query(): iterable
    {
        return [
            'mappings' => ProviderBankCode::filters()->defaultSort('id', 'desc')->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('mappings', [
                TD::make('id', __('ID'))->sort(),
                TD::make('bank_config_key', __('Config Key'))->sort()->filter(Input::make()),
                TD::make('bank_code', __('Bank Code'))->filter(Input::make()),
                TD::make('provider_bank_code', __('Provider Code')),
                TD::make('status', __('Status'))
                    ->render(fn (ProviderBankCode $m) => \App\Enums\EntityStatus::tryFrom($m->status)?->label() ?? $m->status),
                TD::make(__('Actions'))
                    ->render(fn (ProviderBankCode $m) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['mapping' => $m->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('mapping.bank_config_key')->title(__('Config Key'))->required(),
                Input::make('mapping.bank_code')->title(__('Bank Code'))->required(),
                Input::make('mapping.provider_bank_code')->title(__('Provider Bank Code'))->required(),
                Select::make('mapping.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Create Mapping'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('mapping.bank_config_key')->title(__('Config Key'))->required(),
                Input::make('mapping.bank_code')->title(__('Bank Code'))->required(),
                Input::make('mapping.provider_bank_code')->title(__('Provider Bank Code'))->required(),
                Select::make('mapping.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Edit Mapping'))->applyButton(__('Save'))->async('asyncGetMapping'),
        ];
    }

    public function asyncGetMapping(ProviderBankCode $mapping): iterable
    {
        return [
            'mapping' => $mapping,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'mapping.bank_config_key' => 'required|string|max:64',
            'mapping.bank_code' => 'required|string|max:32',
            'mapping.provider_bank_code' => 'required|string|max:64',
            'mapping.status' => 'required',
        ]);

        $id = $request->input('mapping.id');
        $mapping = $id ? ProviderBankCode::findOrFail($id) : new ProviderBankCode();
        $mapping->fill($data['mapping'])->save();

        Toast::info(__('Mapping saved.'));
    }
}
