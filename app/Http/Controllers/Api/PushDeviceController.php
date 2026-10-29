<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;

class PushDeviceController extends Controller
{
    public function save(Request $request)
    {
        abort_unless(config('push.enabled'), 503, 'Push delivery is not configured.');
        $session = $request->user()->currentAccessToken();
        abort_unless($session instanceof PersonalAccessToken, 403);
        $data = $request->validate(['token' => 'required|string|min:20|max:4096']);
        PushDevice::updateOrCreate(['token_hash' => hash('sha256', $data['token'])], [
            'user_id' => $request->user()->id, 'personal_access_token_id' => $session->id, 'token' => $data['token'],
        ]);

        return response()->json(['message' => 'Push device registered.']);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:4096']);
        if (! Schema::hasTable('push_devices')) {
            return response()->json(['message' => 'Push device removed.']);
        }
        PushDevice::where('user_id', $request->user()->id)
            ->where('personal_access_token_id', $request->user()->currentAccessToken()?->id)
            ->where('token_hash', hash('sha256', $data['token']))->delete();

        return response()->json(['message' => 'Push device removed.']);
    }
}
