<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountNotifications;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($this->payload($request->user()))->header('Cache-Control', 'private, no-store');
    }

    public function stream(Request $request)
    {
        $service = app(AccountNotifications::class);
        $user = $service->actor($request->user());
        $accessToken = $request->user()->currentAccessToken();
        $tokenId = $accessToken instanceof PersonalAccessToken ? $accessToken->getKey() : null;

        return response()->stream(function () use ($user, $service, $tokenId) {
            $lastSignature = null;
            $deadline = microtime(true) + 25;
            $lastHeartbeat = microtime(true);
            while (! connection_aborted() && microtime(true) < $deadline) {
                if (! $service->canAccess($user->fresh())) {
                    break;
                }
                if ($tokenId !== null) {
                    $token = PersonalAccessToken::find($tokenId);
                    $expiration = config('sanctum.expiration');
                    if (! $token || ($token->expires_at && $token->expires_at->isPast())
                        || ($expiration && $token->created_at->lte(now()->subMinutes($expiration)))) {
                        break;
                    }
                }
                $payload = $this->payload($user);
                $signature = hash('sha256', json_encode($payload));
                if ($signature !== $lastSignature) {
                    echo "event: notifications\n";
                    echo 'data: '.json_encode($payload)."\n\n";
                    @ob_flush();
                    flush();
                    $lastSignature = $signature;
                    $lastHeartbeat = microtime(true);
                } elseif (microtime(true) - $lastHeartbeat >= 15) {
                    echo ": heartbeat\n\n";
                    @ob_flush();
                    flush();
                    $lastHeartbeat = microtime(true);
                }
                sleep(1);
            }
        }, 200, ['Content-Type' => 'text/event-stream', 'Cache-Control' => 'private, no-store, no-transform',
            'X-Accel-Buffering' => 'no']);
    }

    private function payload($user): array
    {
        $service = app(AccountNotifications::class);

        return ['unread_count' => $service->unread($user), 'notifications' => $service->query($user)->limit(30)->get()
            ->map(fn ($n) => ['id' => $n->id, 'type' => $n->type, 'data' => array_replace($n->data, ['link' => parse_url($service->destination($user, $n->data), PHP_URL_PATH)]),
                'read_at' => $n->read_at, 'created_at' => $n->created_at])];
    }

    public function markAsRead(Request $request, string $id)
    {
        app(AccountNotifications::class)->read($request->user(), $id);

        return $this->result($request, 'Marked as read');
    }

    public function markAllAsRead(Request $request)
    {
        app(AccountNotifications::class)->read($request->user());

        return $this->result($request, 'All marked as read');
    }

    public function destroy(Request $request, string $id)
    {
        app(AccountNotifications::class)->delete($request->user(), $id);

        return $this->result($request, 'Cleared');
    }

    public function destroyAll(Request $request)
    {
        app(AccountNotifications::class)->delete($request->user());

        return $this->result($request, 'All cleared');
    }

    private function result(Request $request, string $message)
    {
        return response()->json(['message' => $message,
            'unread_count' => app(AccountNotifications::class)->unread($request->user())])->header('Cache-Control', 'private, no-store');
    }
}
