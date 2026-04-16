<?php

declare(strict_types=1);

namespace App\Orchid\Layouts\User;

use Orchid\Screen\Field;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Layouts\Rows;

class UserEditLayout extends Rows
{
    /**
     * The screen's layout elements.
     *
     * @return Field[]
     */
    public function fields(): array
    {
        return [
            Input::make('user.username')
                ->type('text')
                ->max(50)
                ->required()
                ->title(__('Username'))
                ->placeholder(__('Username')),

            Input::make('user.name')
                ->type('text')
                ->max(255)
                ->required()
                ->title(__('Name'))
                ->placeholder(__('Name')),

            Input::make('user.email')
                ->type('email')
                ->title(__('Email'))
                ->placeholder(__('Email')),

            Input::make('user.organization')
                ->type('text')
                ->max(100)
                ->title(__('Organization'))
                ->placeholder(__('Organization')),

            TextArea::make('user.notes')
                ->title(__('Notes'))
                ->rows(3)
                ->placeholder(__('Notes')),

            CheckBox::make('user.is_active')
                ->title(__('Active'))
                ->sendTrueOrFalse()
                ->value(true),
        ];
    }
}
