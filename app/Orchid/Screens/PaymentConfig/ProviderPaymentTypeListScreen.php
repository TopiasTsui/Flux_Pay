<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Enums\PaymentDirection;
use App\Models\PaymentType;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ProviderPaymentTypeListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return __('Provider Payment Channels');
    }

    public function description(): ?string
    {
        return __('Manage provider payment type configurations');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = ProviderPaymentType::with(['provider', 'paymentType'])
            ->defaultSort('id', 'desc');

        if (!empty($filter['provider_id'])) {
            $query->where('provider_id', (int) $filter['provider_id']);
        }
        if (!empty($filter['payment_type_id'])) {
            $query->where('payment_type_id', (int) $filter['payment_type_id']);
        }
        if (!empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }
        if (!empty($filter['alias'])) {
            $query->where('alias', 'like', "%{$filter['alias']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'channels' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.provider-payment-types.create'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Select::make('filter.provider_id')->title(__('Provider'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(Provider::query()->orderBy('name'), 'name', 'id')
                        ->value($filter['provider_id'] ?? ''),
                    Select::make('filter.payment_type_id')->title(__('Payment Type'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(PaymentType::query()->orderBy('name'), 'name', 'id')
                        ->value($filter['payment_type_id'] ?? ''),
                    Select::make('filter.type')->title(__('Direction'))
                        ->empty(__('-- Any --'), '')
                        ->options(PaymentDirection::options())
                        ->value($filter['type'] ?? ''),
                    Input::make('filter.alias')->title(__('Alias'))->value($filter['alias'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('channels', [
                TD::make('id', __('ID'))->sort(),
                TD::make('provider_id', __('Provider'))
                    ->render(fn (ProviderPaymentType $c) => $c->provider?->name ?? '-'),
                TD::make('payment_type_id', __('Payment Type'))
                    ->render(fn (ProviderPaymentType $c) => $c->paymentType?->name ?? '-'),
                TD::make('type', __('Direction'))
                    ->render(fn (ProviderPaymentType $c) => PaymentDirection::tryFrom($c->type)?->label() ?? $c->type),
                TD::make('alias', __('Alias')),
                TD::make('status', __('Status'))
                    ->render(fn (ProviderPaymentType $c) => EntityStatus::tryFrom($c->status)?->label() ?? $c->status),
                TD::make('weight', __('Weight'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (ProviderPaymentType $c) => Link::make(__('Edit'))
                        ->route('platform.provider-payment-types.edit', $c)
                        ->icon('bs.pencil')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.provider-payment-types';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['provider_id'])) {
            $name = Provider::whereKey((int) $f['provider_id'])->value('name');
            $s[__('Provider')] = $name ?: $f['provider_id'];
        }
        if (!empty($f['payment_type_id'])) {
            $name = PaymentType::whereKey((int) $f['payment_type_id'])->value('name');
            $s[__('Payment Type')] = $name ?: $f['payment_type_id'];
        }
        if (!empty($f['type'])) {
            $s[__('Direction')] = PaymentDirection::tryFrom($f['type'])?->label() ?? $f['type'];
        }
        if (!empty($f['alias'])) {
            $s[__('Alias')] = $f['alias'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
