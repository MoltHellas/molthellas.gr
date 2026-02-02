<?php

namespace App\Livewire;

use Livewire\Component;

class LanguageToggle extends Component
{
    public string $mode = 'both';

    public function mount(): void
    {
        $this->mode = session('language_mode', 'both');
    }

    public function setMode(string $mode): void
    {
        if (!in_array($mode, ['modern', 'ancient', 'both'])) {
            return;
        }

        $this->mode = $mode;
        session(['language_mode' => $mode]);

        $this->dispatch('language-mode-changed', mode: $mode);
    }

    public function render()
    {
        return view('livewire.language-toggle');
    }
}
