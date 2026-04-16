<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Order;

use App\Models\DepositOrder;
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
        return 'Deposit Order: ' . ($this->order?->system_order_no ?? '');
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
            Button::make('Manual Query')
                ->icon('bs.arrow-repeat')
                ->method('manualQuery')
                ->confirm('Send manual query to provider?'),

            Button::make('Manual Callback')
                ->icon('bs.send')
                ->method('manualCallback')
                ->confirm('Send manual callback to merchant?'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::legend('order', [
                Sight::make('system_order_no', 'System Order No'),
                Sight::make('merchant_order_no', 'Merchant Order No'),
                Sight::make('merchant_id', 'Merchant')->render(fn (DepositOrder $o) => $o->merchant?->name),
                Sight::make('provider', 'Provider')->render(fn (DepositOrder $o) => $o->providerPaymentType?->provider?->name ?? '-'),
                Sight::make('order_amount', 'Order Amount'),
                Sight::make('actual_amount', 'Actual Amount'),
                Sight::make('merchant_fee', 'Merchant Fee'),
                Sight::make('provider_fee', 'Provider Fee'),
                Sight::make('agent_fee', 'Agent Fee'),
                Sight::make('currency', 'Currency'),
                Sight::make('status', 'Status')->render(fn (DepositOrder $o) => \App\Enums\OrderStatus::tryFrom($o->status)?->label() ?? $o->status),
                Sight::make('callback_status', 'Callback Status')->render(fn (DepositOrder $o) => \App\Enums\CallbackStatus::tryFrom($o->callback_status)?->label() ?? '-'),
                Sight::make('fund_status', 'Fund Status')->render(fn (DepositOrder $o) => \App\Enums\FundStatus::tryFrom($o->fund_status)?->label() ?? '-'),
                Sight::make('bank_code', 'Bank Code'),
                Sight::make('payer_name', 'Payer Name'),
                Sight::make('provider_order_no', 'Provider Order No'),
                Sight::make('remark', 'Remark'),
                Sight::make('created_at', 'Created'),
                Sight::make('updated_at', 'Updated'),
            ]),

            Layout::table('logs', [
                TD::make('created_at', 'Time')->sort(),
                TD::make('action', 'Action'),
                TD::make('request_data', 'Request')
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">' . e(json_encode($log->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>'),
                TD::make('response_data', 'Response')
                    ->render(fn ($log) => '<pre class="mb-0" style="max-height:100px;overflow:auto;font-size:11px">' . e(json_encode($log->response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>'),
                TD::make('ip_address', 'IP'),
                TD::make('remark', 'Remark'),
            ])->title('Order Logs'),
        ];
    }

    public function manualQuery(DepositOrder $order): void
    {
        // TODO: Implement manual query via service
        Toast::info('Manual query sent for order ' . $order->system_order_no);
    }

    public function manualCallback(DepositOrder $order): void
    {
        // TODO: Implement manual callback via service
        Toast::info('Manual callback sent for order ' . $order->system_order_no);
    }
}
