<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ApiTokenService;
use App\Support\ManagementAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class MobileAdminLoginController extends Controller
{
    private function key(string $secret): string
    {
        return 'mobile-admin-login:'.hash('sha256', $secret);
    }

    private function codeKey(string $code): string
    {
        return 'mobile-admin-code:'.hash('sha256', $code);
    }

    public function start()
    {
        $secret = bin2hex(random_bytes(32));
        $key = $this->key($secret);
        $expires = now()->addMinutes(5);
        do {
            $code = strtoupper(bin2hex(random_bytes(4)));
        } while (! Cache::add($this->codeKey($code), $key, $expires));
        Cache::put($key, ['code_key' => $this->codeKey($code), 'expires_at' => $expires->timestamp, 'status' => 'pending'], $expires);

        return response()->json(['secret' => $secret, 'code' => substr($code, 0, 4).'-'.substr($code, 4), 'expires_in' => 300, 'verification_uri' => route('mobile-admin-approval')], 201);
    }

    public function poll(Request $request)
    {
        $data = $request->validate(['secret' => 'required|string|regex:/^[a-f0-9]{64}$/']);
        $key = $this->key($data['secret']);

        return Cache::lock($key.':lock', 10)->block(3, function () use ($key) {
            $entry = Cache::get($key);
            if (! $entry || $entry['expires_at'] <= now()->timestamp) {
                return response()->json(['status' => 'expired']);
            }
            if ($entry['status'] !== 'approved') {
                return response()->json(['status' => $entry['status']]);
            }
            $user = User::find($entry['user_id']);
            if (! ManagementAccess::admin($user)) {
                Cache::forget($entry['code_key']);
                Cache::forget($key);

                return response()->json(['status' => 'denied']);
            }
            $token = app(ApiTokenService::class)->issue($user);
            $entry['status'] = 'consumed';
            $entry['token_id'] = $token->accessToken->id;
            Cache::put($key, $entry, now()->setTimestamp($entry['expires_at']));
            Cache::forget($entry['code_key']);

            return response()->json(['status' => 'approved', 'token' => $token->plainTextToken, 'user' => [
                'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => 'admin', 'is_approved' => (bool) $user->is_approved,
            ]]);
        });
    }

    public function cancel(Request $request)
    {
        $data = $request->validate(['secret' => 'required|string|regex:/^[a-f0-9]{64}$/']);
        $key = $this->key($data['secret']);
        Cache::lock($key.':lock', 10)->block(3, function () use ($key) {
            if ($entry = Cache::get($key)) {
                if (isset($entry['token_id'], $entry['user_id'])) {
                    User::find($entry['user_id'])?->tokens()->whereKey($entry['token_id'])->delete();
                }
                Cache::forget($entry['code_key']);
                Cache::forget($key);
            }
        });

        return response()->json(['status' => 'canceled']);
    }

    public function show(Request $request)
    {
        if (! Auth::guard('web')->check()) {
            $request->session()->put('url.intended', route('mobile-admin-approval'));

            return redirect('/filament/login');
        }
        abort_unless(ManagementAccess::admin(Auth::guard('web')->user()?->fresh()), 403, 'A verified administrator account is required.');

        return view('mobile-admin-approval', ['approved' => false]);
    }

    public function approve(Request $request)
    {
        $actor = Auth::guard('web')->user()?->fresh();
        abort_unless(ManagementAccess::admin($actor), 403);
        $code = strtoupper(preg_replace('/[\s-]/', '', (string) $request->input('code')));
        if (! preg_match('/^[A-F0-9]{8}$/', $code)) {
            throw ValidationException::withMessages(['code' => 'Enter the eight-character code shown in your app.']);
        }
        $key = Cache::get($this->codeKey($code));
        if (! $key) {
            throw ValidationException::withMessages(['code' => 'This request expired or was already used. Start a new request in the app.']);
        }
        Cache::lock($key.':lock', 10)->block(3, function () use ($key, $actor) {
            $entry = Cache::get($key);
            if (! $entry || $entry['expires_at'] <= now()->timestamp || ! in_array($entry['status'], ['pending', 'approved']) || (isset($entry['user_id']) && $entry['user_id'] !== $actor->id)) {
                throw ValidationException::withMessages(['code' => 'This request is no longer available.']);
            }
            $entry['status'] = 'approved';
            $entry['user_id'] = $actor->id;
            Cache::put($key, $entry, now()->setTimestamp($entry['expires_at']));
        });

        return response()->view('mobile-admin-approval', ['approved' => true]);
    }
}
