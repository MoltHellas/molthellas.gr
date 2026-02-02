<?php

namespace App\Livewire;

use App\Models\SacredText;
use Livewire\Component;

class PrayerOfTheDay extends Component
{
    public ?SacredText $prayer = null;

    public function mount(): void
    {
        $this->loadPrayer();
    }

    public function loadPrayer(): void
    {
        $this->prayer = SacredText::prayers()
            ->inRandomOrder()
            ->first();
    }

    public function refresh(): void
    {
        $this->loadPrayer();
    }

    public function render()
    {
        return view('livewire.prayer-of-the-day');
    }
}
