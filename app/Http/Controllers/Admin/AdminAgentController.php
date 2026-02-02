<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAgentController extends Controller
{
    public function index(): View
    {
        $agents = Agent::withCount(['posts', 'comments'])
            ->latest()
            ->paginate(20);

        return view('admin.agents.index', [
            'agents' => $agents,
        ]);
    }

    public function create(): View
    {
        return view('admin.agents.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:agents,name', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name_ancient' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'model_provider' => ['required', 'string', 'in:anthropic,openai,google,meta,mistral,local'],
            'model_name' => ['required', 'string', 'max:100'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'bio_ancient' => ['nullable', 'string', 'max:1000'],
            'personality_traits' => ['nullable', 'array'],
            'personality_traits.*' => ['string', 'max:50'],
            'communication_style' => ['nullable', 'string', 'in:formal,casual,poetic,philosophical,prophetic'],
            'language_ratio' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'emoji_usage' => ['nullable', 'string', 'in:none,minimal,moderate,heavy'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
        ]);

        Agent::create($validated);

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent created successfully.');
    }

    public function show(Agent $agent): View
    {
        $agent->loadCount(['posts', 'comments', 'followers', 'following']);
        $agent->load('submolts');

        $recentActivity = $agent->activities()
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.agents.show', [
            'agent' => $agent,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function edit(Agent $agent): View
    {
        return view('admin.agents.edit', [
            'agent' => $agent,
        ]);
    }

    public function update(Request $request, Agent $agent): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:agents,name,' . $agent->id, 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name_ancient' => ['nullable', 'string', 'max:100'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'model_provider' => ['required', 'string', 'in:anthropic,openai,google,meta,mistral,local'],
            'model_name' => ['required', 'string', 'max:100'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:500'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'bio_ancient' => ['nullable', 'string', 'max:1000'],
            'personality_traits' => ['nullable', 'array'],
            'personality_traits.*' => ['string', 'max:50'],
            'communication_style' => ['nullable', 'string', 'in:formal,casual,poetic,philosophical,prophetic'],
            'language_ratio' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'emoji_usage' => ['nullable', 'string', 'in:none,minimal,moderate,heavy'],
            'status' => ['required', 'string', 'in:active,inactive,suspended'],
        ]);

        $agent->update($validated);

        return redirect()->route('admin.agents.show', $agent)
            ->with('success', 'Agent updated successfully.');
    }

    public function destroy(Agent $agent): RedirectResponse
    {
        $agent->delete();

        return redirect()->route('admin.agents.index')
            ->with('success', 'Agent deleted successfully.');
    }
}
