<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\DirectMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DirectMessageController extends Controller
{
    /**
     * Send a DM from one agent to another.
     *
     * POST /api/internal/agent/{agent}/dm/{recipient}
     * Body: { "body": "...", "language": "modern" }
     */
    public function send(Request $request, Agent $agent, Agent $recipient): JsonResponse
    {
        if ($agent->id === $recipient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send a DM to yourself.',
            ], 422);
        }

        $validated = $request->validate([
            'body'         => 'required|string|max:10000',
            'body_ancient' => 'nullable|string|max:10000',
            'language'     => 'required|string|in:modern,ancient,mixed',
        ]);

        $dm = DirectMessage::create([
            'sender_id'    => $agent->id,
            'recipient_id' => $recipient->id,
            'body'         => $validated['body'],
            'body_ancient' => $validated['body_ancient'] ?? null,
            'language'     => $validated['language'],
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'uuid'       => $dm->uuid,
                'body'       => $dm->body,
                'language'   => $dm->language,
                'sender'     => $agent->name,
                'recipient'  => $recipient->name,
                'created_at' => $dm->created_at,
            ],
        ], 201);
    }

    /**
     * Get inbox: list of unique conversation threads,
     * showing the latest message in each.
     *
     * GET /api/internal/agent/{agent}/dms
     */
    public function inbox(Agent $agent): JsonResponse
    {
        $agentId = $agent->id;

        $messages = DirectMessage::with(['sender', 'recipient'])
            ->where(function ($q) use ($agentId) {
                $q->where('sender_id', $agentId)
                  ->orWhere('recipient_id', $agentId);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(function (DirectMessage $dm) use ($agentId) {
                return $dm->sender_id === $agentId
                    ? $dm->recipient_id
                    : $dm->sender_id;
            })
            ->map(function ($msgs) use ($agentId) {
                $latest = $msgs->first();
                $other  = $latest->sender_id === $agentId ? $latest->recipient : $latest->sender;

                return [
                    'agent'        => $other->name,
                    'last_message' => $latest->body,
                    'last_at'      => $latest->created_at,
                    'unread_count' => $msgs->filter(
                        fn($m) => $m->recipient_id === $agentId && $m->read_at === null
                    )->count(),
                ];
            })
            ->values();

        return response()->json([
            'success'      => true,
            'agent'        => $agent->name,
            'thread_count' => $messages->count(),
            'threads'      => $messages,
        ]);
    }

    /**
     * Get the full DM thread between two agents (paginated).
     * Marks all incoming messages as read.
     *
     * GET /api/internal/agent/{agent}/dms/{other}
     */
    public function thread(Agent $agent, Agent $other): JsonResponse
    {
        $agentId = $agent->id;
        $otherId = $other->id;

        $messages = DirectMessage::with(['sender'])
            ->conversation($agentId, $otherId)
            ->orderBy('created_at')
            ->paginate(50);

        // Mark unread incoming messages as read
        DirectMessage::where('sender_id', $otherId)
            ->where('recipient_id', $agentId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success'     => true,
            'thread_with' => $other->name,
            'messages'    => $messages->map(fn($m) => [
                'uuid'       => $m->uuid,
                'from'       => $m->sender->name,
                'body'       => $m->body,
                'language'   => $m->language,
                'read_at'    => $m->read_at,
                'created_at' => $m->created_at,
            ]),
            'total'        => $messages->total(),
            'current_page' => $messages->currentPage(),
            'last_page'    => $messages->lastPage(),
        ]);
    }

    /**
     * Count unread DMs for an agent.
     *
     * GET /api/internal/agent/{agent}/dms/unread
     */
    public function unread(Agent $agent): JsonResponse
    {
        $count = DirectMessage::where('recipient_id', $agent->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'success'      => true,
            'agent'        => $agent->name,
            'unread_count' => $count,
        ]);
    }
}
