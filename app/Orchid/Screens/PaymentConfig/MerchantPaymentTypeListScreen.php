<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use App\Models\Merchant;
use App\Models\MerchantPaymentType;
use App\Models\PaymentType;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantPaymentTypeListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return __('Merchant Payment Configs');
    }

    public function description(): ?string
    {
        return __('Manage merchant fee configurations per payment type');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = MerchantPaymentType::with(['merchant', 'paymentType'])
            ->defaultSort('id', 'desc');

        if (!empty($filter['merchant_id'])) {
            $query->where('merchant_id', (int) $filter['merchant_id']);
        }
        if (!empty($filter['payment_type_id'])) {
            $query->where('payment_type_id', (int) $filter['payment_type_id']);
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'configs' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.merchant-payment-types.create'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Select::make('filter.merchant_id')->title(__('Merchant'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(Merchant::query()->orderBy('code'), 'code', 'id')
                        ->value($filter['merchant_id'] ?? ''),
                    Select::make('filter.payment_type_id')->title(__('Payment Type'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(PaymentType::query()->orderBy('name'), 'name', 'id')
                        ->value($filter['payment_type_id'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('configs', [
                TD::make('id', __('ID'))->sort(),
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (MerchantPaymentType $c) => $c->merchant?->code ?? '-'),
                TD::make('payment_type_id', __('Payment Type'))
                    ->render(fn (MerchantPaymentType $c) => $c->paymentType?->name ?? '-'),
                TD::make('status', __('Status'))
                    ->render(fn (MerchantPaymentType $c) => EntityStatus::tryFrom($c->status)?->label() ?? $c->status),
                TD::make('deposit_fee_type', __('Deposit Fee Type'))
                    ->render(fn (MerchantPaymentType $c) => FeeType::tryFrom($c->deposit_fee_type)?->label() ?? '-'),
                TD::make('deposit_fee', __('Deposit Fee')),
                TD::make('withdraw_fee_type', __('Withdraw Fee Type'))
                    ->render(fn (MerchantPaymentType $c) => FeeType::tryFrom($c->withdraw_fee_type)?->label() ?? '-'),
                TD::make('withdraw_fee', __('Withdraw Fee')),
                TD::make(__('Actions'))
                    ->render(fn (MerchantPaymentType $c) => Link::make(__('Edit'))
                        ->route('platform.merchant-payment-types.edit', $c)
                        ->icon('bs.pencil')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.merchant-payment-types';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['merchant_id'])) {
            $code = Merchant::whereKey((int) $f['merchant_id'])->value('code');
            $s[__('Merchant')] = $code ?: $f['merchant_id'];
        }
        if (!empty($f['payment_type_id'])) {
            $name = PaymentType::whereKey((int) $f['payment_type_id'])->value('name');
            $s[__('Payment Type')] = $name ?: $f['payment_type_id'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
