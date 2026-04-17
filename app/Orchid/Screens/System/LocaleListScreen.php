<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Models\Locale;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use App\Services\Translation\TranslationService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class LocaleListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.system.i18n';

    public function name(): ?string
    {
        return __('Locales');
    }

    public function description(): ?string
    {
        return __('Manage UI languages');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Locale::query()->orderBy('sort_order');

        if (!empty($filter['code'])) {
            $query->where('code', 'like', "%{$filter['code']}%");
        }
        if (isset($filter['is_active']) && $filter['is_active'] !== '') {
            $query->where('is_active', (bool) $filter['is_active']);
        }

        return [
            'locales' => $query->paginate(50),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.code')->title(__('Code'))->value($filter['code'] ?? ''),
                    Select::make('filter.is_active')->title(__('Active'))
                        ->empty(__('-- Any --'), '')
                        ->options([1 => __('Active'), 0 => __('Inactive')])
                        ->value($filter['is_active'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('locales', [
                TD::make('id', __('ID'))->sort(),
                TD::make('code', __('Code'))->sort(),
                TD::make('name', __('Name')),
                TD::make('is_default', __('Default'))
                    ->render(fn (Locale $l) => $l->is_default ? '★' : ''),
                TD::make('is_active', __('Active'))
                    ->render(fn (Locale $l) => $l->is_active
                        ? '<span class="text-success">●</span>'
                        : '<span class="text-danger">●</span>'),
                TD::make('sort_order', __('Sort'))->sort(),
                TD::make(__('Actions'))->alignRight()
                    ->render(fn (Locale $l) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['locale' => $l->id])),
            ]),

            Layout::modal('createModal', Layout::rows($this->formFields()))
                ->title(__('Create Locale'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows($this->formFields()))
                ->title(__('Edit Locale'))->applyButton(__('Save'))->async('asyncGetLocale'),
        ];
    }

    private function formFields(): array
    {
        return [
            Input::make('locale.id')->type('hidden'),
            Input::make('locale.code')->title(__('Code'))->required()->help('e.g. en, zh-CN'),
            Input::make('locale.name')->title(__('Name'))->required(),
            Input::make('locale.sort_order')->title(__('Sort'))->type('number')->value(0),
            Switcher::make('locale.is_default')->title(__('Default'))->sendTrueOrFalse(),
            Switcher::make('locale.is_active')->title(__('Active'))->sendTrueOrFalse()->value(true),
        ];
    }

    public function asyncGetLocale(Locale $locale): iterable
    {
        return ['locale' => $locale];
    }

    public function save(Request $request, TranslationService $service): void
    {
        $data = $request->validate([
            'locale.code' => 'required|string|max:10',
            'locale.name' => 'required|string|max:50',
            'locale.sort_order' => 'nullable|integer',
            'locale.is_default' => 'nullable',
            'locale.is_active' => 'nullable',
        ]);

        $payload = $data['locale'];
        $payload['is_default'] = filter_var($payload['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $payload['is_active'] = filter_var($payload['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);

        $id = $request->input('locale.id');
        $locale = $id ? Locale::findOrFail($id) : new Locale();
        $locale->fill($payload)->save();

        // Only one default locale at a time.
        if ($payload['is_default']) {
            Locale::where('id', '!=', $locale->id)->update(['is_default' => false]);
        }

        $service->forgetAll();

        Toast::info(__('Locale saved.'));
    }

    protected function filterRoute(): string
    {
        return 'platform.system.i18n.locales';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['code'])) {
            $s[__('Code')] = $f['code'];
        }
        if (isset($f['is_active']) && $f['is_active'] !== '') {
            $s[__('Active')] = ((int) $f['is_active']) === 1 ? __('Active') : __('Inactive');
        }

        return $s;
    }
}
