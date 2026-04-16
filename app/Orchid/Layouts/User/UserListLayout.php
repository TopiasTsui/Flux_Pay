<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use App\Models\User;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\DropDown;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Layouts\Persona;
use Orchid\Screen\Layouts\Table;
use Orchid\Screen\TD;

class UserListLayout extends Table
{
    /**
     * @var string
     */
    public $target = 'users';

    /**
     * @return TD[]
     */
    public function columns(): array
    {
        return [
            TD::make('username', __('Username'))
                ->sort()
                ->cantHide()
                ->filter(Input::make()),

            TD::make('name', __('Name'))
                ->sort()
                ->cantHide()
                ->filter(Input::make())
                ->render(fn (User $user) => new Persona($user->presenter())),

            TD::make('email', __('Email'))
                ->sort()
                ->filter(Input::make())
                ->render(fn (User $user) => ModalToggle::make($user->email ?? '-')
                    ->modal('editUserModal')
                    ->modalTitle($user->presenter()->title())
                    ->method('saveUser')
                    ->asyncParameters([
                        'user' => $user->id,
                    ])),

            TD::make('is_active', __('Active'))
                ->sort()
                ->render(fn (User $user) => $user->is_active
                    ? '<span class="text-success">&#9679;</span>'
                    : '<span class="text-danger">&#9679;</span>'),

            TD::make('created_at', __('Created'))
                ->sort()
                ->align(TD::ALIGN_RIGHT)
                ->defaultHidden()
                ->render(fn (User $user) => $user->created_at?->format('Y-m-d H:i:s')),

            TD::make('updated_at', __('Last edit'))
                ->sort()
                ->align(TD::ALIGN_RIGHT)
                ->render(fn (User $user) => $user->updated_at?->format('Y-m-d H:i:s')),

            TD::make(__('Actions'))
                ->align(TD::ALIGN_CENTER)
                ->width('100px')
                ->render(fn (User $user) => DropDown::make()
                    ->icon('bs.three-dots-vertical')
                    ->list([

                        Link::make(__('Edit'))
                            ->route('platform.systems.users.edit', $user->id)
                            ->icon('bs.pencil'),

                        Button::make(__('Delete'))
                            ->icon('bs.trash3')
                            ->confirm(__('Once the account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.'))
                            ->method('remove', [
                                'id' => $user->id,
                            ]),
                    ])),
        ];
    }
}
