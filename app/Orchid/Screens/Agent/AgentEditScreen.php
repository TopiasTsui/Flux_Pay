<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Agent;

use App\Enums\AgentType;
use App\Enums\Currency;
use App\Enums\EntityStatus;
use App\Enums\WalletOperationType;
use App\Models\Agent;
use App\Services\Wallet\AgentWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Relation;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
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
        return ['agent' => $agent];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Back'))->icon('bs.arrow-left')->route('platform.agents'),
            Button::make(__('Save'))->icon('bs.check')->method('save'),
            Button::make(__('Delete'))->icon('bs.trash')->method('remove')
                ->confirm(__('Are you sure you want to delete this agent?'))
                ->canSee($this->agent?->exists ?? false),
        ];
    }

    public function layout(): iterable
    {
        $layouts = [
            Layout::rows([
                Input::make('agent.name')->title(__('Name'))->required(),
                Select::make('agent.types')->title(__('Type'))
                    ->options(collect(AgentType::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())->required(),
                Select::make('agent.level')->title(__('Level'))
                    ->options([1 => __('Level 1'), 2 => __('Level 2'), 3 => __('Level 3')])->required(),
                Relation::make('agent.parent_id')->title(__('Parent Agent'))->fromModel(Agent::class, 'name'),
                Select::make('agent.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Select::make('agent.currency')->title(__('Currency'))->options(Currency::options())->required(),
            ]),
        ];

        if ($this->agent?->exists) {
            $layouts[] = Layout::rows([
                Input::make('_balance_info')->title(__('Current Balance'))->readonly()->disabled()
                    ->value(__('Available') . ': ' . $this->agent->available_balance . '  |  ' . __('Hold') . ': ' . $this->agent->hold_balance . '  |  ' . __('Total') . ': ' . $this->agent->total_balance),
                Select::make('adjust.operation')->title(__('Operation'))
                    ->options(['credit' => __('Manual Credit'), 'debit' => __('Manual Debit')])->empty(__('-- Select --')),
                Input::make('adjust.amount')->title(__('Adjustment Amount'))->type('number')->step('0.01'),
                TextArea::make('adjust.remark')->title(__('Adjustment Remark'))->rows(2),
                Button::make(__('Submit Adjustment'))->icon('bs.wallet2')->method('adjust')
                    ->confirm(__('Are you sure you want to adjust the wallet?')),
            ])->title(__('Wallet Adjustment'));
        }

        return $layouts;
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
        if ($isNew) {
            $username = Str::slug($agentData['name']);
            $user = \App\Models\User::create([
                'username' => $username,
                'name' => $agentData['name'],
                'password' => Hash::make($username),
                'agent_id' => $agent->id,
                'is_active' => true,
            ]);
            $role = Role::where('slug', 'agent')->first();
            if ($role) $user->addRole($role);
        }
        Toast::info(__('Saved successfully.'));
        return redirect()->route('platform.agents');
    }

    public function adjust(Agent $agent, Request $request, AgentWalletService $walletService): RedirectResponse
    {
        $request->validate([
            'adjust.operation' => 'required|in:credit,debit',
            'adjust.amount' => 'required|numeric|min:0.01',
            'adjust.remark' => 'nullable|string|max:255',
        ]);
        $op = $request->input('adjust.operation');
        $amount = (string) $request->input('adjust.amount');
        $remark = $request->input('adjust.remark', '');
        $type = $op === 'credit' ? WalletOperationType::MANUAL_CREDIT : WalletOperationType::MANUAL_DEBIT;
        if ($op === 'credit') {
            $walletService->credit($agent->id, $amount, $type, null, $remark);
        } else {
            $walletService->debit($agent->id, $amount, $type, null, $remark);
        }
        Toast::info(__('Adjustment saved successfully.'));
        return redirect()->route('platform.agents.edit', $agent);
    }

    public function remove(Agent $agent): RedirectResponse
    {
        $agent->delete();
        Toast::info(__('Agent deleted.'));
        return redirect()->route('platform.agents');
    }
}
