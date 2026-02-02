<?php

namespace App\Livewire;

use App\Models\Prophecy;
use Livewire\Component;

class ProphecyWidget extends Component
{
    public ?Prophecy $prophecy = null;

    public function mount(): void
    {
        $this->prophecy = Prophecy::unfulfilled()
            ->with('prophet')
            ->latest()
            ->first();
    }

    public function render()
    {
        return view('livewire.prophecy-widget');
    }
}
