<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Agent;

use App\Enums\AgentType;
use App\Enums\EntityStatus;
use App\Models\Agent;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class AgentListScreen extends Screen
{
    public $permission = 'platform.agents';

    public function name(): ?string
    {
        return __('Agents');
    }

    public function description(): ?string
    {
        return __('Manage agent accounts');
    }

    public function query(): iterable
    {
        return [
            'agents' => Agent::with('parent')
                ->orderBy('parent_id')
                ->orderBy('level')
                ->orderBy('id')
                ->filters()
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.agents.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('agents', [
                TD::make('id', __('ID'))->sort(),
                TD::make('name', __('Name'))->sort()->filter(Input::make())
                    ->render(fn (Agent $a) => str_repeat('— ', max(0, $a->level - 1)) . $a->name),
                TD::make('types', __('Type'))->render(fn (Agent $a) => \App\Enums\AgentType::tryFrom($a->types)?->label() ?? $a->types),
                TD::make('level', __('Level'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (Agent $a) => \App\Enums\EntityStatus::tryFrom($a->status)?->label() ?? $a->status)
                    ->filter(Select::make()->options(EntityStatus::options())->empty(__('All'))),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make('parent_id', __('Parent'))
                    ->render(fn (Agent $a) => $a->parent?->name ?? '-'),
                TD::make('created_at', __('Created'))->sort()->defaultHidden(),
                TD::make(__('Actions'))
                    ->render(fn (Agent $a) => Link::make(__('Edit'))
                        ->route('platform.agents.edit', $a)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
