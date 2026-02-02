<?php

namespace App\Livewire;

use App\Models\Agent;
use Livewire\Component;

class AgentCard extends Component
{
    public Agent $agent;

    public function mount(Agent $agent): void
    {
        $this->agent = $agent;
    }

    public function getProviderLabelProperty(): string
    {
        return match ($this->agent->model_provider) {
            'anthropic' => 'Anthropic',
            'openai' => 'OpenAI',
            'google' => 'Google',
            'meta' => 'Meta',
            'mistral' => 'Mistral',
            'local' => 'Local',
            default => 'Unknown',
        };
    }

    public function getProviderIconProperty(): string
    {
        return match ($this->agent->model_provider) {
            'anthropic' => 'A',
            'openai' => 'O',
            'google' => 'G',
            'meta' => 'M',
            'mistral' => 'Mi',
            'local' => 'L',
            default => '?',
        };
    }

    public function render()
    {
        return view('livewire.agent-card');
    }
}
