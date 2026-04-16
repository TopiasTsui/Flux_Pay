<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Agent;

use App\Enums\AgentType;
use App\Enums\Currency;
use App\Enums\EntityStatus;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class AgentEditScreen extends Screen
{
    public $permission = 'platform.agents';

    public ?Agent $agent = null;

    public function name(): ?string
    {
        return $this->agent?->exists ? 'Edit Agent' : 'Create Agent';
    }

    public function query(Agent $agent): iterable
    {
        return [
            'agent' => $agent,
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Button::make('Save')
                ->icon('bs.check')
                ->method('save'),

            Button::make('Delete')
                ->icon('bs.trash')
                ->method('remove')
                ->confirm('Are you sure you want to delete this agent?')
                ->canSee($this->agent?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('agent.name')
                    ->title('Name')
                    ->required(),

                Select::make('agent.types')
                    ->title('Type')
                    ->options(collect(AgentType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->required(),

                Select::make('agent.level')
                    ->title('Level')
                    ->options([1 => 'Level 1', 2 => 'Level 2', 3 => 'Level 3'])
                    ->required(),

                Relation::make('agent.parent_id')
                    ->title('Parent Agent')
                    ->fromModel(Agent::class, 'name'),

                Select::make('agent.status')
                    ->title('Status')
                    ->options(EntityStatus::options())
                    ->required(),

                Select::make('agent.currency')
                    ->title('Currency')
                    ->options(Currency::options())
                    ->required(),
            ]),
        ];
    }

    public function save(Agent $agent, Request $request): void
    {
        $data = $request->validate([
            'agent.name' => 'required|string|max:255',
            'agent.types' => ['required', Rule::enum(AgentType::class)],
            'agent.level' => 'required|in:1,2,3',
            'agent.parent_id' => 'nullable|exists:agents,id',
            'agent.status' => ['required', Rule::enum(EntityStatus::class)],
            'agent.currency' => ['required', Rule::enum(Currency::class)],
        ]);

        $agentData = $data['agent'];

        // Validate level matches parent
        if (!empty($agentData['parent_id'])) {
            $parent = Agent::find($agentData['parent_id']);
            if ($parent && $agentData['level'] <= $parent->level) {
                Toast::error('Agent level must be greater than parent level.');
                return;
            }
        }

        $agent->fill($agentData)->save();

        Toast::info('Agent saved successfully.');
    }

    public function remove(Agent $agent): void
    {
        $agent->delete();
        Toast::info('Agent deleted.');
    }
}
