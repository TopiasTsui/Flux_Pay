<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Enums\EntityStatus;
use App\Models\Proxy;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class ProxyScreen extends Screen
{
    public $permission = 'platform.system';

    public function name(): ?string
    {
        return __('Proxies');
    }

    public function description(): ?string
    {
        return __('Manage proxy servers');
    }

    public function query(): iterable
    {
        return [
            'proxies' => Proxy::filters()->defaultSort('priority')->paginate(),
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
        return [
            Layout::table('proxies', [
                TD::make('id', __('ID'))->sort(),
                TD::make('name', __('Name'))->sort()->filter(Input::make()),
                TD::make('protocol', __('Protocol')),
                TD::make('host', __('Host')),
                TD::make('port', __('Port')),
                TD::make('status', __('Status'))
                    ->render(fn (Proxy $p) => \App\Enums\EntityStatus::tryFrom($p->status)?->label() ?? $p->status),
                TD::make('priority', __('Priority'))->sort(),
                TD::make('description', __('Description')),
                TD::make(__('Actions'))
                    ->render(fn (Proxy $p) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['proxy' => $p->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('proxy.name')->title(__('Name'))->required(),
                Select::make('proxy.protocol')->title(__('Protocol'))
                    ->options(['http' => 'HTTP', 'https' => 'HTTPS', 'socks5' => 'SOCKS5'])->required(),
                Input::make('proxy.host')->title(__('Host'))->required(),
                Input::make('proxy.port')->title(__('Port'))->type('number')->required(),
                Input::make('proxy.username')->title(__('Username')),
                Input::make('proxy.password')->title(__('Password'))->type('password'),
                Select::make('proxy.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Input::make('proxy.priority')->title(__('Priority'))->type('number')->value(0),
                Input::make('proxy.description')->title(__('Description')),
            ]))->title(__('Create Proxy'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('proxy.name')->title(__('Name'))->required(),
                Select::make('proxy.protocol')->title(__('Protocol'))
                    ->options(['http' => 'HTTP', 'https' => 'HTTPS', 'socks5' => 'SOCKS5'])->required(),
                Input::make('proxy.host')->title(__('Host'))->required(),
                Input::make('proxy.port')->title(__('Port'))->type('number')->required(),
                Input::make('proxy.username')->title(__('Username')),
                Input::make('proxy.password')->title(__('Password'))->type('password'),
                Select::make('proxy.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Input::make('proxy.priority')->title(__('Priority'))->type('number'),
                Input::make('proxy.description')->title(__('Description')),
            ]))->title(__('Edit Proxy'))->applyButton(__('Save'))->async('asyncGetProxy'),
        ];
    }

    public function asyncGetProxy(Proxy $proxy): iterable
    {
        return [
            'proxy' => $proxy,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'proxy.name' => 'required|string|max:64',
            'proxy.protocol' => 'required|in:http,https,socks5',
            'proxy.host' => 'required|string|max:255',
            'proxy.port' => 'required|integer|min:1|max:65535',
            'proxy.username' => 'nullable|string|max:128',
            'proxy.password' => 'nullable|string|max:128',
            'proxy.status' => 'required',
            'proxy.priority' => 'nullable|integer',
            'proxy.description' => 'nullable|string|max:255',
        ]);

        $id = $request->input('proxy.id');
        $proxy = $id ? Proxy::findOrFail($id) : new Proxy();

        $proxyData = $data['proxy'];
        // Don't overwrite password with empty value on edit
        if ($proxy->exists && empty($proxyData['password'])) {
            unset($proxyData['password']);
        }

        $proxy->fill($proxyData)->save();

        Toast::info(__('Proxy saved.'));
    }
}
