<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Wallet;

use App\Enums\WalletOperationType;
use App\Models\Merchant;
use App\Models\MerchantWalletRecord;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantWalletListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.wallets';

    public function name(): ?string
    {
        return __('Merchant Wallet Records');
    }

    public function description(): ?string
    {
        return __('View merchant wallet transaction history');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = MerchantWalletRecord::with('merchant')
            ->orderBy('created_at', 'desc');

        if (!empty($filter['merchant_id'])) {
            $query->where('merchant_id', (int) $filter['merchant_id']);
        }
        if (!empty($filter['sn'])) {
            $query->where('sn', 'like', "%{$filter['sn']}%");
        }
        if (!empty($filter['system_order_no'])) {
            $query->where('system_order_no', 'like', "%{$filter['system_order_no']}%");
        }
        if (!empty($filter['type_code'])) {
            $query->where('type_code', $filter['type_code']);
        }
        if (!empty($filter['date']['start'])) {
            $query->where('created_at', '>=', $filter['date']['start']);
        }
        if (!empty($filter['date']['end'])) {
            $query->where('created_at', '<=', $filter['date']['end']);
        }

        return [
            'records' => $query->paginate(),
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
                    Select::make('filter.merchant_id')->title(__('Merchant'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(Merchant::query()->orderBy('code'), 'code', 'id')
                        ->value($filter['merchant_id'] ?? ''),
                    Select::make('filter.type_code')->title(__('Type'))
                        ->empty(__('-- Any --'), '')
                        ->options(WalletOperationType::options())
                        ->value($filter['type_code'] ?? ''),
                    Input::make('filter.sn')->title(__('SN'))->value($filter['sn'] ?? ''),
                    Input::make('filter.system_order_no')->title(__('Order No'))
                        ->value($filter['system_order_no'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('records', [
                TD::make('id', __('ID'))->sort(),
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (MerchantWalletRecord $r) => $r->merchant?->code ?? '-'),
                TD::make('sn', __('SN')),
                TD::make('type_code', __('Type'))
                    ->render(fn (MerchantWalletRecord $r) => WalletOperationType::tryFrom($r->type_code)?->label() ?? $r->type_code),
                TD::make('amount', __('Amount'))->sort()->alignRight(),
                TD::make('pre_available_balance', __('Before'))->alignRight(),
                TD::make('available_balance', __('After'))->alignRight(),
                TD::make('system_order_no', __('Order No')),
                TD::make('remark', __('Remark')),
                TD::make('created_at', __('Created'))->sort()
                    ->render(fn (MerchantWalletRecord $r) => $r->created_at?->format('Y-m-d H:i:s')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.wallets.merchant';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['date']['start']) || !empty($f['date']['end'])) {
            $s[__('Date')] = ($f['date']['start'] ?? '…') . ' ~ ' . ($f['date']['end'] ?? '…');
        }
        if (!empty($f['merchant_id'])) {
            $code = Merchant::whereKey((int) $f['merchant_id'])->value('code');
            $s[__('Merchant')] = $code ?: $f['merchant_id'];
        }
        if (!empty($f['type_code'])) {
            $s[__('Type')] = WalletOperationType::tryFrom($f['type_code'])?->label() ?? $f['type_code'];
        }
        if (!empty($f['sn'])) {
            $s[__('SN')] = $f['sn'];
        }
        if (!empty($f['system_order_no'])) {
            $s[__('Order No')] = $f['system_order_no'];
        }

        return $s;
    }
}
