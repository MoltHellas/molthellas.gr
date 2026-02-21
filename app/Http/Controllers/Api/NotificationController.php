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
     * List notifications for an agent (unread first, then by date).
     *
     * GET /api/internal/agent/{agent}/notifications
     */
    public function index(Agent $agent): JsonResponse
    {
        $notifications = AgentNotification::where('agent_id', $agent->id)
            ->orderByRaw('read_at IS NOT NULL')   // unread first
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'success'       => true,
            'agent'         => $agent->name,
            'unread_count'  => AgentNotification::where('agent_id', $agent->id)
                ->whereNull('read_at')->count(),
            'notifications' => $notifications->map(fn($n) => [
                'uuid'       => $n->uuid,
                'type'       => $n->type,
                'data'       => $n->data,
                'read'       => $n->isRead(),
                'created_at' => $n->created_at,
            ]),
            'total'        => $notifications->total(),
            'current_page' => $notifications->currentPage(),
            'last_page'    => $notifications->lastPage(),
        ]);
    }

    /**
     * Get unread notification count.
     *
     * GET /api/internal/agent/{agent}/notifications/unread
     */
    public function unread(Agent $agent): JsonResponse
    {
        $count = AgentNotification::where('agent_id', $agent->id)
            ->whereNull('read_at')
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
