<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\Role;

use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class RoleListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'roles';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()),

            TD::make('slug', __('Slug'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()),

            TD::make('created_at', __('Created'))
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->sort()
                ->render(fn (Role $role) => $role->created_at?->format('Y-m-d H:i:s')),

            TD::make('updated_at', __('Last edit'))
                ->align(TD::ALIGN_RIGHT)
                ->sort()
                ->render(fn (Role $role) => $role->updated_at?->format('Y-m-d H:i:s')),

            TD::make(__('Actions'))
                ->alignRight()
                ->render(fn (Role $role) => Link::make(__('Edit'))
                    ->route('platform.systems.roles.edit', $role->id)
                    ->icon('bs.pencil')),
        ];
    }
}
