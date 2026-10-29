<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\PushDevice;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

class SendBookingPush implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function __construct(public int $deviceId, public int $bookingId, public int $userId, public int $sessionId, public bool $reminder = false) {}

    public function handle(): void
    {
        if (! config('push.enabled')) {
            return;
        }
        $device = PushDevice::find($this->deviceId);
        if (! $device || $device->user_id != $this->userId || $device->personal_access_token_id != $this->sessionId) {
            return;
        }
        $session = PersonalAccessToken::find($device->personal_access_token_id);
        if (! $session || $session->tokenable_type !== (new User)->getMorphClass() || $session->tokenable_id != $device->user_id
            || ($session->expires_at && $session->expires_at->isPast())
            || (config('sanctum.expiration') && $session->created_at->addMinutes(config('sanctum.expiration'))->isPast())) {
            return;
        }
        $booking = Booking::with('salon')->find($this->bookingId);
        if (! $booking || ($booking->user_id != $device->user_id && $booking->salon?->created_by != $device->user_id)) {
            return;
        }
        if ($this->reminder && ($booking->status !== 'approved' ||
            Carbon::parse($booking->date->format('Y-m-d').' '.$booking->time, 'Asia/Colombo')->isPast())) {
            return;
        }
        $project = config('push.project_id');
        if (! $project || ! is_file((string) config('push.credentials'))) {
            throw new \RuntimeException('Firebase server configuration is missing.');
        }
        $access = Cache::remember('push-oauth-'.hash('sha256', $project), 3000, function () {
            $client = new Client;
            $client->setAuthConfig(config('push.credentials'));
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $result = $client->fetchAccessTokenWithAssertion();

            return $result['access_token'] ?? throw new \RuntimeException('Firebase authorization failed.');
        });
        $response = Http::withToken($access)->timeout(15)->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($project).'/messages:send', [
            'message' => ['token' => $device->token,
                'notification' => ['title' => 'BookMyStyle', 'body' => $this->reminder ? 'You have an upcoming appointment. Open the app for details.' : 'A booking has been updated. Open the app for details.'],
                'data' => ['user_id' => (string) $device->user_id, 'booking_id' => (string) $booking->id],
                'android' => ['priority' => 'high', 'ttl' => '3600s']],
        ]);
        $codes = collect($response->json('error.details', []))->pluck('errorCode');
        if ($codes->contains('UNREGISTERED')) {
            $device->delete();

            return;
        }
        if ($response->status() === 401) {
            Cache::forget('push-oauth-'.hash('sha256', $project));
        }
        // Never include request tokens, service-account material or response bodies in job errors.
        if (! $response->successful()) {
            throw new \RuntimeException('Push delivery failed (HTTP '.$response->status().').');
        }
    }
}
