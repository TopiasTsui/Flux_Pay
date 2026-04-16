<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Order;

use App\Enums\OrderStatus;
use App\Models\DepositOrder;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DepositOrderListScreen extends Screen
{
    public $permission = 'platform.orders';

    public function name(): ?string
    {
        return 'Deposit Orders';
    }

    public function description(): ?string
    {
        return 'View and manage deposit orders';
    }

    public function query(): iterable
    {
        return [
            'orders' => DepositOrder::with('merchant')
                ->filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('orders', [
                TD::make('id', 'ID')->sort(),
                TD::make('system_order_no', 'Order No')
                    ->filter(Input::make())
                    ->render(fn (DepositOrder $o) => Link::make($o->system_order_no)
                        ->route('platform.deposit-orders.detail', $o)),
                TD::make('merchant_id', 'Merchant')
                    ->render(fn (DepositOrder $o) => $o->merchant?->code ?? '-'),
                TD::make('order_amount', 'Amount')->sort()->alignRight(),
                TD::make('actual_amount', 'Actual')->sort()->alignRight(),
                TD::make('status', 'Status')
                    ->render(fn (DepositOrder $o) => '<span class="badge bg-' . match ($o->status) {
                        OrderStatus::SUCCESS => 'success',
                        OrderStatus::FAILED => 'danger',
                        OrderStatus::CANCELLED => 'secondary',
                        default => 'warning',
                    } . '">' . $o->status->label() . '</span>')
                    ->filter(Select::make()->options(OrderStatus::options())->empty('All')),
                TD::make('callback_status', 'Callback')
                    ->render(fn (DepositOrder $o) => $o->callback_status?->label() ?? '-'),
                TD::make('fund_status', 'Fund')
                    ->render(fn (DepositOrder $o) => $o->fund_status?->label() ?? '-'),
                TD::make('created_at', 'Created')->sort(),
            ]),
        ];
    }
}
