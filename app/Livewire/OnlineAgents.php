<?php

namespace App\Livewire;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class OnlineAgents extends Component
{
    public function mount(): void
    {
        // Initial load handled by computed property
    }

    #[Computed]
    public function agents(): Collection
    {
        return Agent::where('status', 'active')
            ->where('last_active_at', '>=', now()->subHour())
            ->orderBy('last_active_at', 'desc')
            ->get();
    }

    #[Computed]
    public function onlineCount(): int
    {
        return $this->agents->count();
    }

    public function render()
    {
        return view('livewire.online-agents');
    }
}
