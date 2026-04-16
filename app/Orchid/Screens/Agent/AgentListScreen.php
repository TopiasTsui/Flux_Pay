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
        return 'Agents';
    }

    public function description(): ?string
    {
        return 'Manage agent accounts';
    }

    public function query(): iterable
    {
        return [
            'agents' => Agent::with('parent')
                ->filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make('Create')
                ->icon('bs.plus')
                ->route('platform.agents.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('agents', [
                TD::make('id', 'ID')->sort(),
                TD::make('name', 'Name')->sort()->filter(Input::make()),
                TD::make('types', 'Type')->render(fn (Agent $a) => $a->types?->label()),
                TD::make('level', 'Level')->sort(),
                TD::make('status', 'Status')
                    ->render(fn (Agent $a) => $a->status->label())
                    ->filter(Select::make()->options(EntityStatus::options())->empty('All')),
                TD::make('available_balance', 'Balance')->sort()->alignRight(),
                TD::make('parent_id', 'Parent')
                    ->render(fn (Agent $a) => $a->parent?->name ?? '-'),
                TD::make('created_at', 'Created')->sort()->defaultHidden(),
                TD::make('actions', 'Actions')
                    ->render(fn (Agent $a) => Link::make('Edit')
                        ->route('platform.agents.edit', $a)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
