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
        return 'Provider Bank Code Mappings';
    }

    public function description(): ?string
    {
        return 'Map system bank codes to provider-specific codes';
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
            ModalToggle::make('Create')
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('mappings', [
                TD::make('id', 'ID')->sort(),
                TD::make('bank_config_key', 'Config Key')->sort()->filter(Input::make()),
                TD::make('bank_code', 'Bank Code')->filter(Input::make()),
                TD::make('provider_bank_code', 'Provider Code'),
                TD::make('status', 'Status')
                    ->render(fn (ProviderBankCode $m) => $m->status->label()),
                TD::make('actions', 'Actions')
                    ->render(fn (ProviderBankCode $m) => ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['mapping' => $m->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('mapping.bank_config_key')->title('Config Key')->required(),
                Input::make('mapping.bank_code')->title('Bank Code')->required(),
                Input::make('mapping.provider_bank_code')->title('Provider Bank Code')->required(),
                Select::make('mapping.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Create Mapping')->applyButton('Save'),

            Layout::modal('editModal', Layout::rows([
                Input::make('mapping.bank_config_key')->title('Config Key')->required(),
                Input::make('mapping.bank_code')->title('Bank Code')->required(),
                Input::make('mapping.provider_bank_code')->title('Provider Bank Code')->required(),
                Select::make('mapping.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Edit Mapping')->applyButton('Save')->async('asyncGetMapping'),
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

        Toast::info('Mapping saved.');
    }
}
