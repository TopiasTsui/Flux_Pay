<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Order;

use App\Models\WithdrawOrder;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class WithdrawOrderDetailScreen extends Screen
{
    public $permission = 'platform.orders';

    public ?WithdrawOrder $order = null;

    public function name(): ?string
    {
        return __('Withdraw Order') . ': ' . ($this->order?->system_order_no ?? '');
    }

    public function query(WithdrawOrder $order): iterable
    {
        $order->load(['merchant', 'providerPaymentType.provider', 'logs']);

        return [
            'order' => $order,
            'logs' => $order->logs()->orderBy('created_at', 'desc')->get(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make(__('Manual Query'))
                ->icon('bs.arrow-repeat')
                ->method('manualQuery')
                ->confirm(__('Send manual query to provider?')),

            Button::make(__('Manual Callback'))
                ->icon('bs.send')
                ->method('manualCallback')
                ->confirm(__('Send manual callback to merchant?')),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('order', [
                Sight::make('system_order_no', __('System Order No')),
                Sight::make('merchant_order_no', __('Merchant Order No')),
                Sight::make('merchant_id', __('Merchant'))->render(fn (WithdrawOrder $o) => $o->merchant?->name),
                Sight::make('provider', __('Provider'))->render(fn (WithdrawOrder $o) => $o->providerPaymentType?->provider?->name ?? '-'),
                Sight::make('order_amount', __('Order Amount')),
                Sight::make('actual_amount', __('Actual Amount')),
                Sight::make('total_debit', __('Total Debit')),
                Sight::make('merchant_fee', __('Merchant Fee')),
                Sight::make('provider_fee', __('Provider Fee')),
                Sight::make('agent_fee', __('Agent Fee')),
                Sight::make('currency', __('Currency')),
                Sight::make('bank_code', __('Bank Code')),
                Sight::make('bank_account_name', __('Account Name')),
                Sight::make('bank_account_no', __('Account No')),
                Sight::make('bank_branch', __('Branch')),
                Sight::make('status', __('Status'))->render(fn (WithdrawOrder $o) => \App\Enums\OrderStatus::tryFrom($o->status)?->label() ?? $o->status),
                Sight::make('callback_status', __('Callback Status'))->render(fn (WithdrawOrder $o) => \App\Enums\CallbackStatus::tryFrom($o->callback_status)?->label() ?? '-'),
                Sight::make('fund_status', __('Fund Status'))->render(fn (WithdrawOrder $o) => \App\Enums\FundStatus::tryFrom($o->fund_status)?->label() ?? '-'),
                Sight::make('provider_order_no', __('Provider Order No')),
                Sight::make('remark', __('Remark')),
                Sight::make('created_at', __('Created')),
                Sight::make('updated_at', __('Updated')),
            ]),

            Layout::table('logs', [
                TD::make('created_at', __('Time'))->sort(),
                TD::make('action', __('Action')),
                TD::make('request_data', __('Request'))
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">' . e(json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>'),
                TD::make('response_data', __('Response'))
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">' . e(json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>'),
                TD::make('ip_address', __('IP')),
                TD::make('remark', __('Remark')),
            ])->title(__('Order Logs')),
        ];
    }

    public function manualQuery(WithdrawOrder $order): void
    {
        Toast::info(__('Manual query sent for order') . ' ' . $order->system_order_no);
    }

    public function manualCallback(WithdrawOrder $order): void
    {
        Toast::info(__('Manual callback sent for order') . ' ' . $order->system_order_no);
    }
}
