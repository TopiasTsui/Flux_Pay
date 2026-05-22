<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Order;

use App\Enums\CallbackStatus;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Models\DepositOrder;
use App\Services\Order\DepositService;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Screen;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class DepositOrderDetailScreen extends Screen
{
    public $permission = 'platform.orders';

    public ?DepositOrder $order = null;

    public function name(): ?string
    {
        return __('Deposit Order').': '.($this->order?->system_order_no ?? '');
    }

    public function query(DepositOrder $order): iterable
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
                Sight::make('merchant_id', __('Merchant'))->render(fn (DepositOrder $o) => $o->merchant?->name),
                Sight::make('provider', __('Provider'))->render(fn (DepositOrder $o) => $o->providerPaymentType?->provider?->name ?? '-'),
                Sight::make('order_amount', __('Order Amount')),
                Sight::make('actual_amount', __('Actual Amount')),
                Sight::make('merchant_fee', __('Merchant Fee')),
                Sight::make('provider_fee', __('Provider Fee')),
                Sight::make('agent_fee', __('Agent Fee')),
                Sight::make('currency', __('Currency')),
                Sight::make('status', __('Status'))->render(fn (DepositOrder $o) => OrderStatus::tryFrom($o->status)?->label() ?? $o->status),
                Sight::make('callback_status', __('Callback Status'))->render(fn (DepositOrder $o) => CallbackStatus::tryFrom($o->callback_status)?->label() ?? '-'),
                Sight::make('fund_status', __('Fund Status'))->render(fn (DepositOrder $o) => FundStatus::tryFrom($o->fund_status)?->label() ?? '-'),
                Sight::make('bank_code', __('Bank Code')),
                Sight::make('payer_name', __('Payer Name')),
                Sight::make('provider_order_no', __('Provider Order No')),
                Sight::make('remark', __('Remark')),
                Sight::make('created_at', __('Created'))
                    ->render(fn (DepositOrder $o) => $o->created_at?->format('Y-m-d H:i:s')),
                Sight::make('updated_at', __('Updated'))
                    ->render(fn (DepositOrder $o) => $o->updated_at?->format('Y-m-d H:i:s')),
            ]),

            Layout::table('logs', [
                TD::make('created_at', __('Time'))->sort()
                    ->render(fn ($log) => $log->created_at?->format('Y-m-d H:i:s')),
                TD::make('action', __('Action')),
                TD::make('request_data', __('Request'))
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">'.e(json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>'),
                TD::make('response_data', __('Response'))
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">'.e(json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre>'),
                TD::make('ip_address', __('IP')),
                TD::make('remark', __('Remark')),
            ])->title(__('Order Logs')),
        ];
    }

    public function manualQuery(DepositOrder $order): void
    {
        try {
            $result = app(DepositService::class)->manualQuery($order);
        } catch (\Throwable $e) {
            Toast::error(__('Manual query failed').': '.$e->getMessage());

            return;
        }

        if ($result->status?->isFinal()) {
            Toast::success(__('Manual query completed, order updated').' '.$order->system_order_no);
        } else {
            Toast::info(__('Manual query sent, status still pending').' '.$order->system_order_no);
        }
    }

    public function manualCallback(DepositOrder $order): void
    {
        if (app(DepositService::class)->resendMerchantNotification($order)) {
            Toast::success(__('Merchant notification re-sent for order').' '.$order->system_order_no);
        } else {
            Toast::warning(__('Order must be successful and have a notify URL before re-sending'));
        }
    }
}
