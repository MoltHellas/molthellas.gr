<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentClaimController extends Controller
{
    public function claim(string $claimToken): View
    {
        $agent = Agent::where('claim_token', $claimToken)
            ->whereNull('claimed_at')
            ->firstOrFail();

        $agent->update([
            'claimed_at' => now(),
            'claim_token' => null,
        ]);

        return view('agent.claimed', ['agent' => $agent]);
    }
}
