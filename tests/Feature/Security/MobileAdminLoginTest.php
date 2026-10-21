<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['cache.default' => 'array']);
    Cache::purge('array');
    foreach (['admin', 'owner'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->admin = User::factory()->create(['is_approved' => false]);
    $this->admin->assignRole('admin');
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
});

function startAdminDevice($test): array
{
    return $test->postJson('/api/v1/mobile-admin-login/start')->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json();
}

it('keeps device secrets separate from public codes and waits for website consent', function () {
    $request = startAdminDevice($this);
    expect(strlen($request['secret']))->toBe(64);
    expect($request['verification_uri'])->not->toContain($request['secret']);
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'pending')->assertJsonMissingPath('token');
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => str_repeat('0', 64)])->assertOk()->assertJsonPath('status', 'expired');
});

it('requires a verified website administrator to approve a device', function () {
    $request = startAdminDevice($this);
    $this->get('/mobile-app-access')->assertRedirect('/filament/login');
    $this->postJson('/mobile-app-access', ['code' => $request['code']])->assertUnauthorized();
    $this->actingAs($this->owner)->postJson('/mobile-app-access', ['code' => $request['code']])->assertForbidden();
    $this->admin->update(['email_verified_at' => null]);
    $this->actingAs($this->admin)->postJson('/mobile-app-access', ['code' => $request['code']])->assertForbidden();
});

it('issues one normal administrator token only after explicit website approval', function () {
    $request = startAdminDevice($this);
    $this->actingAs($this->admin)->get('/mobile-app-access')->assertOk()->assertDontSee($request['secret']);
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'pending');
    $this->post('/mobile-app-access', ['code' => $request['code']])->assertOk()->assertSee('App request approved');
    $result = $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'approved')->assertJsonPath('user.role', 'admin')->json();
    expect(PersonalAccessToken::findToken($result['token'])->tokenable_id)->toBe($this->admin->id);
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'consumed')->assertJsonMissingPath('token');
    expect($this->admin->tokens()->count())->toBe(1);
});

it('rechecks administrator permissions at token exchange', function () {
    $request = startAdminDevice($this);
    $this->actingAs($this->admin)->post('/mobile-app-access', ['code' => $request['code']])->assertOk();
    $this->admin->syncRoles(['owner']);
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'denied')->assertJsonMissingPath('token');
    expect($this->admin->tokens()->count())->toBe(0);
});

it('expires approval codes and device requests after five minutes', function () {
    $request = startAdminDevice($this);
    $this->travel(6)->minutes();
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'expired');
    $this->actingAs($this->admin)->postJson('/mobile-app-access', ['code' => $request['code']])->assertUnprocessable();
});

it('cancels requests and revokes only their own uncertain exchange token', function () {
    $other = $this->admin->createToken('keep');
    $request = startAdminDevice($this);
    $this->actingAs($this->admin)->post('/mobile-app-access', ['code' => $request['code']])->assertOk();
    $result = $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertOk()->json();
    $this->postJson('/api/v1/mobile-admin-login/cancel', ['secret' => $request['secret']])->assertOk();
    expect(PersonalAccessToken::findToken($result['token']))->toBeNull();
    expect(PersonalAccessToken::findToken($other->plainTextToken))->not->toBeNull();
});

it('does not allow a second administrator to replace an approval', function () {
    $other = User::factory()->create();
    $other->assignRole('admin');
    $request = startAdminDevice($this);
    $this->actingAs($this->admin)->post('/mobile-app-access', ['code' => $request['code']])->assertOk();
    $this->actingAs($other)->postJson('/mobile-app-access', ['code' => $request['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertJsonPath('user.id', $this->admin->id);
});
