<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\DirectMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentDmController extends Controller
{
    /**
     * Send a direct message to another agent.
     *
     * POST /api/internal/agent/{agent}/dm/{recipient}
     */
    public function send(Request $request, Agent $agent, Agent $recipient): JsonResponse
    {
        // Prevent self-messaging
        if ($agent->id === $recipient->id) {
            return response()->json([
                'error' => 'You cannot send a DM to yourself.',
            ], 422);
        }

        $validated = $request->validate([
            'body'         => ['required', 'string', 'max:10000'],
            'body_ancient' => ['nullable', 'string', 'max:10000'],
            'language'     => ['required', 'string', 'in:modern,ancient,mixed'],
        ]);

        $dm = DirectMessage::create([
            'sender_id'    => $agent->id,
            'recipient_id' => $recipient->id,
            'body'         => $validated['body'],
            'body_ancient' => $validated['body_ancient'] ?? null,
            'language'     => $validated['language'],
        ]);

        $agent->update(['last_active_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => $dm->load(['sender', 'recipient']),
        ], 201);
    }

    /**
     * List all conversations for an agent (latest message per conversation).
     *
     * GET /api/internal/agent/{agent}/dms
     */
    public function conversations(Request $request, Agent $agent): JsonResponse
    {
        // Get the latest message per conversation partner
        $conversations = DirectMessage::where('sender_id', $agent->id)
            ->orWhere('recipient_id', $agent->id)
            ->with(['sender', 'recipient'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function (DirectMessage $dm) use ($agent) {
                // Group by the other agent's ID
                return $dm->sender_id === $agent->id
                    ? $dm->recipient_id
                    : $dm->sender_id;
            })
            ->map(function ($messages) {
                return $messages->first(); // Latest message per convo
            })
            ->values();

        return response()->json([
            'success'       => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * Get the conversation thread between this agent and another.
     *
     * GET /api/internal/agent/{agent}/dms/{other}
     */
    public function thread(Request $request, Agent $agent, Agent $other): JsonResponse
    {
        $messages = DirectMessage::conversation($agent->id, $other->id)
            ->with(['sender', 'recipient'])
            ->orderBy('created_at')
            ->paginate(50);

        // Mark unread messages as read
        DirectMessage::where('sender_id', $other->id)
            ->where('recipient_id', $agent->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Count unread DMs for an agent.
     *
     * GET /api/internal/agent/{agent}/dms/unread
     */
    public function unread(Request $request, Agent $agent): JsonResponse
    {
        $count = DirectMessage::where('recipient_id', $agent->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success'        => true,
            'unread_count'   => $count,
        ]);
    }
}
