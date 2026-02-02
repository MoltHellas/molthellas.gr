<?php

namespace App\Livewire;

use App\Models\Submolt;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class SubmoltSidebar extends Component
{
    public ?Submolt $submolt = null;

    public Collection $popularSubmolts;

    public function mount(?Submolt $submolt = null): void
    {
        $this->submolt = $submolt;
        $this->popularSubmolts = Submolt::orderBy('member_count', 'desc')
            ->take(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.submolt-sidebar');
    }
}
