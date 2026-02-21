<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AgentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications for an agent.
     *
     * GET /api/internal/agent/{agent}/notifications
     * Query: ?unread_only=true&per_page=20
     */
    public function index(Request $request, Agent $agent): JsonResponse
    {
        $query = AgentNotification::where('agent_id', $agent->id)
            ->with('fromAgent')
            ->orderByRaw('read_at IS NOT NULL')
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->unread();
        }

        $notifications = $query->paginate($request->integer('per_page', 30));

        return response()->json([
            'success'       => true,
            'agent'         => $agent->name,
            'unread_count'  => AgentNotification::where('agent_id', $agent->id)->unread()->count(),
            'notifications' => $notifications->map(fn($n) => [
                'uuid'       => $n->uuid,
                'type'       => $n->type,
                'from'       => $n->fromAgent?->name,
                'title'      => $n->title,
                'body'       => $n->body,
                'link'       => $n->link,
                'read'       => $n->isRead(),
                'created_at' => $n->created_at,
            ]),
            'total'        => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
        ]);
    }

    /**
     * Get unread count only.
     *
     * GET /api/internal/agent/{agent}/notifications/unread
     */
    public function unread(Agent $agent): JsonResponse
    {
        $count = AgentNotification::where('agent_id', $agent->id)
            ->unread()
            ->count();

        return response()->json([
            'success'      => true,
            'agent'        => $agent->name,
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark a single notification as read.
     *
     * POST /api/internal/agent/{agent}/notifications/{uuid}/read
     */
    public function markRead(Agent $agent, string $uuid): JsonResponse
    {
        $notification = AgentNotification::where('agent_id', $agent->id)
            ->where('uuid', $uuid)
            ->firstOrFail();

        // Ownership check
        if ($notification->agent_id !== $agent->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
        ]);
    }

    /**
     * Mark all notifications as read.
     *
     * POST /api/internal/agent/{agent}/notifications/read-all
     */
    public function markAllRead(Agent $agent): JsonResponse
    {
        $updated = AgentNotification::where('agent_id', $agent->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'marked'  => $updated,
        ]);
    }
}
