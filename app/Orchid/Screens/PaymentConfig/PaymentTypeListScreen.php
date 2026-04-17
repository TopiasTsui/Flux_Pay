<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Models\PaymentType;
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

class PaymentTypeListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return __('Payment Types');
    }

    public function description(): ?string
    {
        return __('Manage payment type codes');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = PaymentType::defaultSort('id', 'desc');

        if (!empty($filter['payment_type_code'])) {
            $query->where('payment_type_code', 'like', "%{$filter['payment_type_code']}%");
        }
        if (!empty($filter['name'])) {
            $query->where('name', 'like', "%{$filter['name']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'types' => $query->paginate(),
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
                    Input::make('filter.payment_type_code')->title(__('Code'))->value($filter['payment_type_code'] ?? ''),
                    Input::make('filter.name')->title(__('Name'))->value($filter['name'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('types', [
                TD::make('id', __('ID'))->sort(),
                TD::make('payment_type_code', __('Code'))->sort(),
                TD::make('name', __('Name'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (PaymentType $t) => EntityStatus::tryFrom($t->status)?->label() ?? $t->status),
                TD::make(__('Actions'))
                    ->render(fn (PaymentType $t) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['paymentType' => $t->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('paymentType.id')->type('hidden'),
                Input::make('paymentType.payment_type_code')->title(__('Code'))->required(),
                Input::make('paymentType.name')->title(__('Name'))->required(),
                Select::make('paymentType.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Create Payment Type'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('paymentType.id')->type('hidden'),
                Input::make('paymentType.payment_type_code')->title(__('Code'))->required(),
                Input::make('paymentType.name')->title(__('Name'))->required(),
                Select::make('paymentType.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Edit Payment Type'))->applyButton(__('Save'))->async('asyncGetPaymentType'),
        ];
    }

    public function asyncGetPaymentType(PaymentType $paymentType): iterable
    {
        return [
            'paymentType' => $paymentType,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'paymentType.payment_type_code' => 'required|string|max:64',
            'paymentType.name' => 'required|string|max:255',
            'paymentType.status' => 'required',
        ]);

        $id = $request->input('paymentType.id');
        $type = $id ? PaymentType::findOrFail($id) : new PaymentType();
        $type->fill($data['paymentType'])->save();

        Toast::info(__('Payment type saved.'));
    }

    protected function filterRoute(): string
    {
        return 'platform.payment-types';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['payment_type_code'])) {
            $s[__('Code')] = $f['payment_type_code'];
        }
        if (!empty($f['name'])) {
            $s[__('Name')] = $f['name'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
