<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return response()->json($this->notificationPayload($user));
    }

    public function stream(Request $request)
    {
        $user = $request->user();

        return response()->stream(function () use ($user) {
            $lastSignature = null;
            $lastHeartbeatAt = time();

            while (! connection_aborted()) {
                $payload = $this->notificationPayload($user);
                $signature = md5(json_encode([
                    'unread_count' => $payload['unread_count'],
                    'notifications' => collect($payload['notifications'])->map(fn ($notification) => [
                        'id' => $notification['id'],
                        'read_at' => $notification['read_at'],
                        'created_at' => $notification['created_at'],
                    ])->all(),
                ]));

                if ($signature !== $lastSignature) {
                    echo "event: notifications\n";
                    echo 'data: ' . json_encode($payload) . "\n\n";
                    @ob_flush();
                    flush();

                    $lastSignature = $signature;
                    $lastHeartbeatAt = time();
                } elseif ((time() - $lastHeartbeatAt) >= 15) {
                    echo ": heartbeat\n\n";
                    @ob_flush();
                    flush();

                    $lastHeartbeatAt = time();
                }

                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function notificationPayload($user): array
    {
        $notifications = $user->notifications()
            ->limit(30)
            ->get()
            ->map(function ($n) {
                return [
                    'id'         => $n->id,
                    'type'       => $n->type,
                    'data'       => $n->data,
                    'read_at'    => $n->read_at,
                    'created_at' => $n->created_at,
                ];
            });

        return [
            'unread_count'  => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ];
    }

    public function markAsRead(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message'      => 'Marked as read',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'message'      => 'All marked as read',
            'unread_count' => 0,
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $notification = $user->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $notification->delete();

        return response()->json([
            'message'      => 'Cleared',
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function destroyAll(Request $request)
    {
        $request->user()->notifications()->delete();

        return response()->json([
            'message'      => 'All cleared',
            'unread_count' => 0,
        ]);
    }
}
