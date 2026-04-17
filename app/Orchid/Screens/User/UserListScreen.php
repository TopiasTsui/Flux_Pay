<?php

declare(strict_types=1);

namespace App\Orchid\Screens\User;

use App\Models\User;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use App\Orchid\Layouts\User\UserEditLayout;
use App\Orchid\Layouts\User\UserListLayout;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class UserListScreen extends Screen
{
    use HasFilters;

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = User::with('roles')->defaultSort('id', 'desc');

        if (!empty($filter['search'])) {
            $s = $filter['search'];
            $query->where(function ($q) use ($s) {
                $q->where('username', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if (!empty($filter['role'])) {
            $role = $filter['role'];
            $query->whereHas('roles', fn ($r) => $r->where('slug', $role));
        }

        if (isset($filter['is_active']) && $filter['is_active'] !== '') {
            $query->where('is_active', (bool) $filter['is_active']);
        }

        return [
            'users' => $query->paginate(),
        ];
    }

    public function name(): ?string
    {
        return __('User Management');
    }

    public function description(): ?string
    {
        return __('A comprehensive list of all registered users, including their profiles and privileges.');
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.users',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->route('platform.systems.users.create'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        $roleOptions = Role::orderBy('name')->pluck('name', 'slug')->all();

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.search')->title(__('Search'))
                        ->placeholder(__('Username / name / email'))
                        ->value($filter['search'] ?? ''),
                    Select::make('filter.role')->title(__('Role'))
                        ->empty(__('-- Any --'), '')
                        ->options($roleOptions)
                        ->value($filter['role'] ?? ''),
                    Select::make('filter.is_active')->title(__('Active'))
                        ->empty(__('-- Any --'), '')
                        ->options([1 => __('Active'), 0 => __('Inactive')])
                        ->value($filter['is_active'] ?? ''),
                ],
                summary: $summary,
            ),

            UserListLayout::class,

            Layout::modal('editUserModal', UserEditLayout::class)
                ->deferred('loadUserOnOpenModal'),
        ];
    }

    public function loadUserOnOpenModal(User $user): iterable
    {
        return [
            'user' => $user,
        ];
    }

    public function saveUser(Request $request, User $user): void
    {
        $request->validate([
            'user.username' => [
                'required',
                'string',
                'max:50',
                Rule::unique(User::class, 'username')->ignore($user),
            ],
            'user.email' => [
                'nullable',
                Rule::unique(User::class, 'email')->ignore($user),
            ],
        ]);

        $user->fill($request->input('user'))->save();

        Toast::info(__('User was saved.'));
    }

    public function remove(Request $request): void
    {
        User::findOrFail($request->get('id'))->delete();

        Toast::info(__('User was removed'));
    }

    protected function filterRoute(): string
    {
        return 'platform.systems.users';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['search'])) {
            $s[__('Search')] = $f['search'];
        }
        if (!empty($f['role'])) {
            $name = Role::where('slug', $f['role'])->value('name');
            $s[__('Role')] = $name ?: $f['role'];
        }
        if (isset($f['is_active']) && $f['is_active'] !== '') {
            $s[__('Active')] = ((int) $f['is_active']) === 1 ? __('Active') : __('Inactive');
        }

        return $s;
    }
}
