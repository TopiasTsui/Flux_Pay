<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Agent;

use App\Enums\AgentType;
use App\Enums\Currency;
use App\Enums\EntityStatus;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
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
        return $this->agent?->exists ? __('Edit Agent') : __('Create Agent');
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
            Link::make(__('Back'))
                ->icon('bs.arrow-left')
                ->route('platform.agents'),

            Button::make(__('Save'))
                ->icon('bs.check')
                ->method('save'),

            Button::make(__('Delete'))
                ->icon('bs.trash')
                ->method('remove')
                ->confirm(__('Are you sure you want to delete this agent?'))
                ->canSee($this->agent?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::rows([
                Input::make('agent.name')
                    ->title(__('Name'))
                    ->required(),

                Select::make('agent.types')
                    ->title(__('Type'))
                    ->options(collect(AgentType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
                    ->required(),

                Select::make('agent.level')
                    ->title(__('Level'))
                    ->options([1 => __('Level 1'), 2 => __('Level 2'), 3 => __('Level 3')])
                    ->required(),

                Relation::make('agent.parent_id')
                    ->title(__('Parent Agent'))
                    ->fromModel(Agent::class, 'name'),

                Select::make('agent.status')
                    ->title(__('Status'))
                    ->options(EntityStatus::options())
                    ->required(),

                Select::make('agent.currency')
                    ->title(__('Currency'))
                    ->options(Currency::options())
                    ->required(),
            ]),
        ];
    }

    public function save(Agent $agent, Request $request): RedirectResponse
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
                Toast::error(__('Agent level must be greater than parent level.'));
                return redirect()->route('platform.agents');
            }
        }

        $isNew = !$agent->exists;
        if ($isNew) {
            $agentData['created_by'] = Auth::id();
        }

        $agent->fill($agentData)->save();

        // Create backend user account for new agent
        if ($isNew) {
            $username = Str::slug($agentData['name']);
            $user = \App\Models\User::create([
                'username' => $username,
                'name' => $agentData['name'],
                'password' => \Illuminate\Support\Facades\Hash::make($username),
                'agent_id' => $agent->id,
                'is_active' => true,
            ]);
            $agentRole = \Orchid\Platform\Models\Role::where('slug', 'agent')->first();
            if ($agentRole) {
                $user->addRole($agentRole);
            }
        }

        Toast::info(__('Saved successfully.'));

        return redirect()->route('platform.agents');
    }

    public function remove(Agent $agent): RedirectResponse
    {
        $agent->delete();
        Toast::info(__('Agent deleted.'));

        return redirect()->route('platform.agents');
    }
}
