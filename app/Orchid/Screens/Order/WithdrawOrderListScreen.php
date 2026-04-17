<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Order;

use App\Enums\CallbackStatus;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Models\WithdrawOrder;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class WithdrawOrderListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.orders';

    public function name(): ?string
    {
        return __('Withdraw Orders');
    }

    public function description(): ?string
    {
        return __('View and manage withdrawal orders');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = WithdrawOrder::with('merchant')
            ->defaultSort('id', 'desc');

        if (!empty($filter['system_order_no'])) {
            $query->where('system_order_no', 'like', "%{$filter['system_order_no']}%");
        }
        if (!empty($filter['merchant_order_no'])) {
            $query->where('merchant_order_no', 'like', "%{$filter['merchant_order_no']}%");
        }
        if (!empty($filter['merchant_code'])) {
            $code = $filter['merchant_code'];
            $query->whereHas('merchant', fn ($q) => $q->where('code', 'like', "%{$code}%"));
        }
        if (!empty($filter['bank_account_no'])) {
            $query->where('bank_account_no', 'like', "%{$filter['bank_account_no']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }
        if (isset($filter['fund_status']) && $filter['fund_status'] !== '') {
            $query->where('fund_status', (int) $filter['fund_status']);
        }
        if (!empty($filter['date']['start'])) {
            $query->where('created_at', '>=', $filter['date']['start']);
        }
        if (!empty($filter['date']['end'])) {
            $query->where('created_at', '<=', $filter['date']['end']);
        }

        return [
            'orders' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    DateRange::make('filter.date')->title(__('Date Range'))->value($filter['date'] ?? []),
                    Input::make('filter.system_order_no')->title(__('System Order No'))
                        ->value($filter['system_order_no'] ?? ''),
                    Input::make('filter.merchant_order_no')->title(__('Merchant Order No'))
                        ->value($filter['merchant_order_no'] ?? ''),
                    Input::make('filter.merchant_code')->title(__('Merchant Code'))
                        ->value($filter['merchant_code'] ?? ''),
                    Input::make('filter.bank_account_no')->title(__('Bank Account No'))
                        ->value($filter['bank_account_no'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(OrderStatus::options())
                        ->value($filter['status'] ?? ''),
                    Select::make('filter.fund_status')->title(__('Fund'))
                        ->empty(__('-- Any --'), '')
                        ->options(FundStatus::options())
                        ->value($filter['fund_status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('orders', [
                TD::make('id', __('ID'))->sort(),
                TD::make('system_order_no', __('Order No'))
                    ->render(fn (WithdrawOrder $o) => Link::make($o->system_order_no)
                        ->route('platform.withdraw-orders.detail', $o)),
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (WithdrawOrder $o) => $o->merchant?->code ?? '-'),
                TD::make('order_amount', __('Amount'))->sort()->alignRight(),
                TD::make('actual_amount', __('Actual'))->sort()->alignRight(),
                TD::make('status', __('Status'))
                    ->render(fn (WithdrawOrder $o) => '<span class="badge bg-' . match ($o->status) {
                        OrderStatus::SUCCESS => 'success',
                        OrderStatus::FAILED => 'danger',
                        OrderStatus::CANCELLED => 'secondary',
                        default => 'warning',
                    } . '">' . (OrderStatus::tryFrom($o->status)?->label() ?? $o->status) . '</span>'),
                TD::make('callback_status', __('Callback'))
                    ->render(fn (WithdrawOrder $o) => CallbackStatus::tryFrom($o->callback_status)?->label() ?? '-'),
                TD::make('fund_status', __('Fund'))
                    ->render(fn (WithdrawOrder $o) => FundStatus::tryFrom($o->fund_status)?->label() ?? '-'),
                TD::make('created_at', __('Created'))->sort()
                    ->render(fn (WithdrawOrder $o) => $o->created_at?->format('Y-m-d H:i:s')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.withdraw-orders';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['date']['start']) || !empty($f['date']['end'])) {
            $s[__('Date')] = ($f['date']['start'] ?? '…') . ' ~ ' . ($f['date']['end'] ?? '…');
        }
        if (!empty($f['system_order_no'])) {
            $s[__('System Order No')] = $f['system_order_no'];
        }
        if (!empty($f['merchant_order_no'])) {
            $s[__('Merchant Order No')] = $f['merchant_order_no'];
        }
        if (!empty($f['merchant_code'])) {
            $s[__('Merchant Code')] = $f['merchant_code'];
        }
        if (!empty($f['bank_account_no'])) {
            $s[__('Bank Account No')] = $f['bank_account_no'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = OrderStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }
        if (isset($f['fund_status']) && $f['fund_status'] !== '') {
            $s[__('Fund')] = FundStatus::tryFrom((int) $f['fund_status'])?->label() ?? $f['fund_status'];
        }

        return $s;
    }
}
