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
        return __('Deposit Orders');
    }

    public function description(): ?string
    {
        return __('View and manage deposit orders');
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
                TD::make('id', __('ID'))->sort(),
                TD::make('system_order_no', __('Order No'))
                    ->filter(Input::make())
                    ->render(fn (DepositOrder $o) => Link::make($o->system_order_no)
                        ->route('platform.deposit-orders.detail', $o)),
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (DepositOrder $o) => $o->merchant?->code ?? '-'),
                TD::make('order_amount', __('Amount'))->sort()->alignRight(),
                TD::make('actual_amount', __('Actual'))->sort()->alignRight(),
                TD::make('status', __('Status'))
                    ->render(fn (DepositOrder $o) => '<span class="badge bg-' . match ($o->status) {
                        OrderStatus::SUCCESS => 'success',
                        OrderStatus::FAILED => 'danger',
                        OrderStatus::CANCELLED => 'secondary',
                        default => 'warning',
                    } . '">' . \App\Enums\OrderStatus::tryFrom($o->status)?->label() ?? $o->status . '</span>')
                    ->filter(Select::make()->options(OrderStatus::options())->empty(__('All'))),
                TD::make('callback_status', __('Callback'))
                    ->render(fn (DepositOrder $o) => \App\Enums\CallbackStatus::tryFrom($o->callback_status)?->label() ?? '-'),
                TD::make('fund_status', __('Fund'))
                    ->render(fn (DepositOrder $o) => \App\Enums\FundStatus::tryFrom($o->fund_status)?->label() ?? '-'),
                TD::make('created_at', __('Created'))->sort(),
            ]),
        ];
    }
}
