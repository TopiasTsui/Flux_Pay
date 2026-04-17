<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Bank;

use App\Enums\EntityStatus;
use App\Models\ProviderBankCode;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
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
    use HasFilters;

    public $permission = 'platform.banks';

    public function name(): ?string
    {
        return __('Provider Bank Code Mappings');
    }

    public function description(): ?string
    {
        return __('Map system bank codes to provider-specific codes');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = ProviderBankCode::query()->defaultSort('id', 'desc');

        if (!empty($filter['bank_config_key'])) {
            $query->where('bank_config_key', 'like', "%{$filter['bank_config_key']}%");
        }
        if (!empty($filter['bank_code'])) {
            $query->where('bank_code', 'like', "%{$filter['bank_code']}%");
        }
        if (!empty($filter['provider_bank_code'])) {
            $query->where('provider_bank_code', 'like', "%{$filter['provider_bank_code']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'mappings' => $query->paginate(),
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
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.bank_config_key')->title(__('Config Key'))->value($filter['bank_config_key'] ?? ''),
                    Input::make('filter.bank_code')->title(__('Bank Code'))->value($filter['bank_code'] ?? ''),
                    Input::make('filter.provider_bank_code')->title(__('Provider Code'))->value($filter['provider_bank_code'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('mappings', [
                TD::make('id', __('ID'))->sort(),
                TD::make('bank_config_key', __('Config Key'))->sort(),
                TD::make('bank_code', __('Bank Code')),
                TD::make('provider_bank_code', __('Provider Code')),
                TD::make('status', __('Status'))
                    ->render(fn (ProviderBankCode $m) => EntityStatus::tryFrom($m->status)?->label() ?? $m->status),
                TD::make(__('Actions'))
                    ->render(fn (ProviderBankCode $m) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['mapping' => $m->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('mapping.id')->type('hidden'),
                Input::make('mapping.bank_config_key')->title(__('Config Key'))->required(),
                Input::make('mapping.bank_code')->title(__('Bank Code'))->required(),
                Input::make('mapping.provider_bank_code')->title(__('Provider Bank Code'))->required(),
                Select::make('mapping.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Create Mapping'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('mapping.id')->type('hidden'),
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

    protected function filterRoute(): string
    {
        return 'platform.provider-bank-codes';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['bank_config_key'])) {
            $s[__('Config Key')] = $f['bank_config_key'];
        }
        if (!empty($f['bank_code'])) {
            $s[__('Bank Code')] = $f['bank_code'];
        }
        if (!empty($f['provider_bank_code'])) {
            $s[__('Provider Code')] = $f['provider_bank_code'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
