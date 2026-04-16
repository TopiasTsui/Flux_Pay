<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Models\SystemConfig;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SystemConfigScreen extends Screen
{
    public $permission = 'platform.system';

    public function name(): ?string
    {
        return 'System Configuration';
    }

    public function description(): ?string
    {
        return 'Manage system-wide configuration values';
    }

    public function query(): iterable
    {
        return [
            'configs' => SystemConfig::filters()->defaultSort('group')->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make('Create')
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('configs', [
                TD::make('id', 'ID')->sort(),
                TD::make('group', 'Group')->sort()->filter(Input::make()),
                TD::make('key', 'Key')->sort()->filter(Input::make()),
                TD::make('value', 'Value')
                    ->render(fn (SystemConfig $c) => mb_strlen($c->value) > 80
                        ? mb_substr($c->value, 0, 80) . '...'
                        : $c->value),
                TD::make('remark', 'Remark'),
                TD::make('actions', 'Actions')
                    ->render(fn (SystemConfig $c) => ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['config' => $c->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('config.group')->title('Group')->value('general'),
                Input::make('config.key')->title('Key')->required(),
                TextArea::make('config.value')->title('Value')->required()->rows(3),
                Input::make('config.remark')->title('Remark'),
            ]))->title('Create Config')->applyButton('Save'),

            Layout::modal('editModal', Layout::rows([
                Input::make('config.group')->title('Group'),
                Input::make('config.key')->title('Key')->required(),
                TextArea::make('config.value')->title('Value')->required()->rows(3),
                Input::make('config.remark')->title('Remark'),
            ]))->title('Edit Config')->applyButton('Save')->async('asyncGetConfig'),
        ];
    }

    public function asyncGetConfig(SystemConfig $config): iterable
    {
        return [
            'config' => $config,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'config.group' => 'nullable|string|max:64',
            'config.key' => 'required|string|max:128',
            'config.value' => 'required|string',
            'config.remark' => 'nullable|string|max:255',
        ]);

        $id = $request->input('config.id');
        $config = $id ? SystemConfig::findOrFail($id) : new SystemConfig();
        $config->fill($data['config'])->save();

        Toast::info('Config saved.');
    }
}
