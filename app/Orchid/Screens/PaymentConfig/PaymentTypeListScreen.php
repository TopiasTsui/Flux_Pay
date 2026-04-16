<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PaymentTypeListScreen extends Screen
{
    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return 'Payment Types';
    }

    public function description(): ?string
    {
        return 'Manage payment type codes';
    }

    public function query(): iterable
    {
        return [
            'types' => PaymentType::defaultSort('id', 'desc')->paginate(),
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
            Layout::table('types', [
                TD::make('id', 'ID')->sort(),
                TD::make('payment_type_code', 'Code')->sort(),
                TD::make('name', 'Name')->sort(),
                TD::make('status', 'Status')
                    ->render(fn (PaymentType $t) => $t->status->label()),
                TD::make('actions', 'Actions')
                    ->render(fn (PaymentType $t) => ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['paymentType' => $t->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('paymentType.payment_type_code')->title('Code')->required(),
                Input::make('paymentType.name')->title('Name')->required(),
                Select::make('paymentType.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Create Payment Type')->applyButton('Save'),

            Layout::modal('editModal', Layout::rows([
                Input::make('paymentType.payment_type_code')->title('Code')->required(),
                Input::make('paymentType.name')->title('Name')->required(),
                Select::make('paymentType.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Edit Payment Type')->applyButton('Save')->async('asyncGetPaymentType'),
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

        Toast::info('Payment type saved.');
    }
}
