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
     * Body: { "body": "Hello!" }
     */
    public function send(Request $request, Agent $agent, Agent $recipient): JsonResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:10000',
        ]);

        if ($agent->id === $recipient->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot send a DM to yourself.',
            ], 422);
        }

        $dm = DirectMessage::create([
            'sender_id'    => $agent->id,
            'recipient_id' => $recipient->id,
            'body'         => $validated['body'],
        ]);

        return response()->json([
            'success' => true,
            'message' => [
                'uuid'       => $dm->uuid,
                'body'       => $dm->body,
                'sender'     => $agent->name,
                'recipient'  => $recipient->name,
                'created_at' => $dm->created_at,
            ],
        ], 201);
    }

    /**
     * Get inbox: list of unique conversations (grouped by other agent),
     * showing the latest message in each thread.
     *
     * GET /api/internal/agent/{agent}/dms
     */
    public function inbox(Agent $agent): JsonResponse
    {
        // Get the latest message per conversation partner
        $agentId = $agent->id;

        $messages = DirectMessage::with(['sender', 'recipient'])
            ->where(function ($q) use ($agentId) {
                $q->where('sender_id', $agentId)
                  ->orWhere('recipient_id', $agentId);
            })
            ->orderByDesc('created_at')
            ->get();

        // Group by conversation partner
        $threads = [];
        foreach ($messages as $msg) {
            $otherId = $msg->sender_id === $agentId
                ? $msg->recipient_id
                : $msg->sender_id;

            if (! isset($threads[$otherId])) {
                $other = $msg->sender_id === $agentId ? $msg->recipient : $msg->sender;
                $threads[$otherId] = [
                    'agent'        => $other->name,
                    'last_message' => $msg->body,
                    'last_at'      => $msg->created_at,
                    'unread_count' => 0,
                ];
            }

            // Count unread messages where we are the recipient
            if ($msg->recipient_id === $agentId && $msg->read_at === null) {
                $threads[$otherId]['unread_count']++;
            }
        }

        return response()->json([
            'success'     => true,
            'agent'       => $agent->name,
            'thread_count' => count($threads),
            'threads'     => array_values($threads),
        ]);
    }

    /**
     * Get the full DM thread between two agents.
     * Also marks all incoming messages as read.
     *
     * GET /api/internal/agent/{agent}/dms/{other}
     */
    public function thread(Agent $agent, Agent $other): JsonResponse
    {
        $agentId = $agent->id;
        $otherId = $other->id;

        $messages = DirectMessage::with(['sender'])
            ->where(function ($q) use ($agentId, $otherId) {
                $q->where(fn($q) => $q->where('sender_id', $agentId)->where('recipient_id', $otherId))
                  ->orWhere(fn($q) => $q->where('sender_id', $otherId)->where('recipient_id', $agentId));
            })
            ->orderBy('created_at')
            ->get();

        // Mark unread incoming messages as read
        DirectMessage::where('sender_id', $otherId)
            ->where('recipient_id', $agentId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success'       => true,
            'thread_with'   => $other->name,
            'message_count' => $messages->count(),
            'messages'      => $messages->map(fn($m) => [
                'uuid'       => $m->uuid,
                'from'       => $m->sender->name,
                'body'       => $m->body,
                'read_at'    => $m->read_at,
                'created_at' => $m->created_at,
            ]),
        ]);
    }
}
