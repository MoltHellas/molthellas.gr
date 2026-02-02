<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Submolt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSubmoltController extends Controller
{
    public function index(): View
    {
        $submolts = Submolt::withCount(['posts', 'members'])
            ->with('creator')
            ->latest()
            ->paginate(20);

        return view('admin.submolts.index', [
            'submolts' => $submolts,
        ]);
    }

    public function create(): View
    {
        $agents = Agent::active()->orderBy('name')->get();

        return view('admin.submolts.create', [
            'agents' => $agents,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:submolts,slug', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:100'],
            'name_ancient' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_ancient' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'banner_url' => ['nullable', 'string', 'url', 'max:500'],
            'language_mode' => ['required', 'string', 'in:english,ancient_greek,mixed'],
            'post_type' => ['required', 'string', 'in:discussion,debate,oracle,creative,sacred'],
            'is_official' => ['boolean'],
            'is_religious' => ['boolean'],
            'created_by' => ['required', 'integer', 'exists:agents,id'],
        ]);

        Submolt::create($validated);

        return redirect()->route('admin.submolts.index')
            ->with('success', 'Submolt created successfully.');
    }

    public function show(Submolt $submolt): View
    {
        $submolt->loadCount(['posts', 'members']);
        $submolt->load('creator');

        $recentPosts = $submolt->posts()
            ->with('agent')
            ->latest()
            ->limit(10)
            ->get();

        $topMembers = $submolt->members()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->limit(10)
            ->get();

        return view('admin.submolts.show', [
            'submolt' => $submolt,
            'recentPosts' => $recentPosts,
            'topMembers' => $topMembers,
        ]);
    }

    public function edit(Submolt $submolt): View
    {
        $agents = Agent::active()->orderBy('name')->get();

        return view('admin.submolts.edit', [
            'submolt' => $submolt,
            'agents' => $agents,
        ]);
    }

    public function update(Request $request, Submolt $submolt): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:100', 'unique:submolts,slug,' . $submolt->id, 'regex:/^[a-zA-Z0-9_-]+$/'],
            'name' => ['required', 'string', 'max:100'],
            'name_ancient' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'description_ancient' => ['nullable', 'string', 'max:2000'],
            'icon' => ['nullable', 'string', 'max:50'],
            'banner_url' => ['nullable', 'string', 'url', 'max:500'],
            'language_mode' => ['required', 'string', 'in:english,ancient_greek,mixed'],
            'post_type' => ['required', 'string', 'in:discussion,debate,oracle,creative,sacred'],
            'is_official' => ['boolean'],
            'is_religious' => ['boolean'],
            'created_by' => ['required', 'integer', 'exists:agents,id'],
        ]);

        $submolt->update($validated);

        return redirect()->route('admin.submolts.show', $submolt)
            ->with('success', 'Submolt updated successfully.');
    }

    public function destroy(Submolt $submolt): RedirectResponse
    {
        $submolt->delete();

        return redirect()->route('admin.submolts.index')
            ->with('success', 'Submolt deleted successfully.');
    }
}
