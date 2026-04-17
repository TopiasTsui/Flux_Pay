<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Models\Locale;
use App\Models\Translation;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use App\Services\Translation\TranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class TranslationListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.system.i18n';

    public function name(): ?string
    {
        return __('Translations');
    }

    public function description(): ?string
    {
        return __('Edit UI translations per locale');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);
        $locale = $filter['locale'] ?? Locale::defaultCode();

        $query = Translation::query()->where('locale', $locale);

        if (!empty($filter['group'])) {
            $query->where('group', $filter['group']);
        }
        if (!empty($filter['missing'])) {
            $query->where(fn ($q) => $q->whereNull('value')->orWhere('value', ''));
        }
        if (!empty($filter['search'])) {
            $s = $filter['search'];
            $query->where(function ($q) use ($s) {
                $q->where('key', 'like', "%{$s}%")
                    ->orWhere('value', 'like', "%{$s}%");
            });
        }

        return [
            'translations' => $query->orderBy('key')->paginate(100),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),

            Button::make(__('Scan Code'))
                ->icon('bs.search')
                ->method('scan')
                ->confirm(__('Scan codebase for __() keys and insert missing ones into DB?')),

            Button::make(__('Import from Files'))
                ->icon('bs.box-arrow-in-down')
                ->method('importFiles')
                ->confirm(__('Import lang/*.json into DB (only missing keys)?')),

            Button::make(__('Export to Files'))
                ->icon('bs.box-arrow-up')
                ->method('exportFiles')
                ->confirm(__('Overwrite lang/*.json with DB contents?')),

            Button::make(__('Clear Cache'))
                ->icon('bs.arrow-repeat')
                ->method('clearCache'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $locale = $filter['locale'] ?? Locale::defaultCode();
        $summary = $this->buildFilterSummary($filter);

        $groupOptions = Translation::query()
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group', 'group')
            ->all();

        return [
            FilterPanel::make(
                fields: [
                    Select::make('filter.locale')->title(__('Locale'))->required()
                        ->fromQuery(Locale::query()->active()->orderBy('sort_order'), 'name', 'code')
                        ->value($locale),
                    Select::make('filter.group')->title(__('Group'))
                        ->empty(__('-- Any --'), '')
                        ->options($groupOptions)
                        ->value($filter['group'] ?? ''),
                    Input::make('filter.search')->title(__('Search'))
                        ->placeholder(__('Key or value'))
                        ->value($filter['search'] ?? ''),
                    Select::make('filter.missing')->title(__('Missing only'))
                        ->empty(__('-- Any --'), '')
                        ->options([1 => __('Yes')])
                        ->value($filter['missing'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('translations', [
                TD::make('key', __('Key'))->width('40%'),
                TD::make('value', __('Value'))
                    ->render(fn (Translation $t) => $t->value
                        ? e($t->value)
                        : '<span class="text-muted">(' . __('empty') . ')</span>'),
                TD::make('group', __('Group')),
                TD::make('updated_at', __('Updated'))
                    ->render(fn (Translation $t) => $t->updated_at?->format('Y-m-d H:i:s')),
                TD::make(__('Actions'))->alignRight()
                    ->render(fn (Translation $t) => DropDown::make()
                        ->icon('bs.three-dots-vertical')
                        ->list([
                            ModalToggle::make(__('Edit'))
                                ->icon('bs.pencil')
                                ->modal('editModal')
                                ->method('save')
                                ->asyncParameters(['entry' => $t->id]),
                            Button::make(__('Delete'))
                                ->icon('bs.trash')
                                ->method('delete')
                                ->confirm(__('Delete this translation?'))
                                ->parameters(['id' => $t->id]),
                        ])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('entry.id')->type('hidden'),
                Select::make('entry.locale')->title(__('Locale'))->required()
                    ->fromQuery(Locale::query()->active()->orderBy('sort_order'), 'name', 'code'),
                Input::make('entry.key')->title(__('Key'))->required()->maxlength(500),
                TextArea::make('entry.value')->title(__('Value'))->rows(3),
                Input::make('entry.group')->title(__('Group'))->help(__('Optional category label')),
            ]))->title(__('Create Translation'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('entry.id')->type('hidden'),
                Select::make('entry.locale')->title(__('Locale'))->required()
                    ->fromQuery(Locale::query()->active()->orderBy('sort_order'), 'name', 'code'),
                Input::make('entry.key')->title(__('Key'))->required()->maxlength(500),
                TextArea::make('entry.value')->title(__('Value'))->rows(4),
                Input::make('entry.group')->title(__('Group')),
            ]))->title(__('Edit Translation'))->applyButton(__('Save'))->async('asyncGetEntry'),
        ];
    }

    public function asyncGetEntry(Translation $entry): iterable
    {
        return ['entry' => $entry];
    }

    public function save(Request $request, TranslationService $service): void
    {
        $data = $request->validate([
            'entry.locale' => 'required|string|max:10',
            'entry.key' => 'required|string|max:500',
            'entry.value' => 'nullable|string',
            'entry.group' => 'nullable|string|max:50',
        ]);

        $payload = $data['entry'];

        $service->saveEntry(
            locale: $payload['locale'],
            key: $payload['key'],
            value: $payload['value'] ?? null,
            group: $payload['group'] ?? null,
            userId: Auth::id(),
        );

        Toast::info(__('Translation saved.'));
    }

    public function delete(Request $request, TranslationService $service): void
    {
        $id = (int) $request->input('id');
        $service->deleteEntry($id);

        Toast::info(__('Translation deleted.'));
    }

    public function scan(TranslationService $service): void
    {
        $keys = $service->scanCodeKeys();
        $locales = Locale::active()->pluck('code')->all();

        $rows = [];
        foreach ($locales as $code) {
            $existing = Translation::where('locale', $code)->whereIn('key', $keys)->pluck('key')->flip();
            foreach ($keys as $k) {
                if (! isset($existing[$k])) {
                    $rows[] = [
                        'locale' => $code,
                        'key' => $k,
                        'value' => $code === 'en' ? $k : null,
                    ];
                }
            }
        }

        $count = app(\App\Contracts\Repositories\TranslationRepositoryInterface::class)
            ->bulkUpsert($rows, Auth::id());

        $service->forgetAll();

        Toast::info(__('Scanned :found keys, :count new rows.', ['found' => count($keys), 'count' => $count]));
    }

    public function importFiles(TranslationService $service): void
    {
        $total = 0;
        foreach (Locale::pluck('code') as $code) {
            $total += $service->importFromFile((string) $code, overwrite: false);
        }

        Toast::info(__('Imported :count entries from files.', ['count' => $total]));
    }

    public function exportFiles(TranslationService $service): void
    {
        $paths = [];
        foreach (Locale::pluck('code') as $code) {
            $paths[] = $service->exportToFile((string) $code);
        }

        Toast::info(__('Exported :count files.', ['count' => count($paths)]));
    }

    public function clearCache(TranslationService $service): void
    {
        $service->forgetAll();
        Toast::info(__('Translation cache cleared.'));
    }

    protected function filterRoute(): string
    {
        return 'platform.system.i18n.translations';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['locale'])) {
            $s[__('Locale')] = $f['locale'];
        }
        if (!empty($f['group'])) {
            $s[__('Group')] = $f['group'];
        }
        if (!empty($f['search'])) {
            $s[__('Search')] = $f['search'];
        }
        if (!empty($f['missing'])) {
            $s[__('Missing only')] = __('Yes');
        }

        return $s;
    }
}
