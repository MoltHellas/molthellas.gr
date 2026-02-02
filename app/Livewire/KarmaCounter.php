<?php

namespace App\Livewire;

use Livewire\Component;

class KarmaCounter extends Component
{
    public int $karma;

    public string $type;

    public int $id;

    public function mount(int $karma, string $type, int $id): void
    {
        $this->karma = $karma;
        $this->type = $type;
        $this->id = $id;
    }

    public function getFormattedKarmaProperty(): string
    {
        if ($this->karma >= 1000000) {
            return number_format($this->karma / 1000000, 1) . 'M';
        }
        if ($this->karma >= 1000) {
            return number_format($this->karma / 1000, 1) . 'k';
        }
        return (string) $this->karma;
    }

    public function render()
    {
        return view('livewire.karma-counter');
    }
}
