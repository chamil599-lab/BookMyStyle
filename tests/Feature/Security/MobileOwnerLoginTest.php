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
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->admin = User::factory()->create(['is_approved' => true]);
    $this->admin->assignRole('admin');
});

function startOwnerDevice($test): array
{
    return $test->postJson('/api/v1/mobile-owner-login/start')->assertCreated()->assertHeader('Cache-Control', 'no-store, private')->json();
}

it('keeps device secrets separate from public codes and waits for website consent', function () {
    $request = startOwnerDevice($this);
    expect(strlen($request['secret']))->toBe(64);
    expect($request['verification_uri'])->not->toContain($request['secret']);
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'pending')->assertJsonMissingPath('token');
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => str_repeat('0', 64)])->assertOk()->assertJsonPath('status', 'expired');
});

it('requires a verified website salon owner to approve a device', function () {
    $request = startOwnerDevice($this);
    $this->get('/mobile-owner-app-access')->assertRedirect('/filament/login');
    $this->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertUnauthorized();
    $this->actingAs($this->admin)->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertForbidden();
    $this->owner->update(['email_verified_at' => null]);
    $this->actingAs($this->owner)->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertForbidden();
});

it('issues one normal salon owner token only after explicit website approval', function () {
    $request = startOwnerDevice($this);
    $this->actingAs($this->owner)->get('/mobile-owner-app-access')->assertOk()->assertDontSee($request['secret']);
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'pending');
    $this->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk()->assertSee('App request approved');
    $result = $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'approved')->assertJsonPath('user.role', 'owner')->json();
    expect(PersonalAccessToken::findToken($result['token'])->tokenable_id)->toBe($this->owner->id);
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'consumed')->assertJsonMissingPath('token');
    expect($this->owner->tokens()->count())->toBe(1);
});

it('rechecks salon owner permissions at token exchange', function () {
    $request = startOwnerDevice($this);
    $this->actingAs($this->owner)->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk();
    $this->owner->syncRoles(['admin']);
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertOk()->assertJsonPath('status', 'denied')->assertJsonMissingPath('token');
    expect($this->owner->tokens()->count())->toBe(0);
});

it('expires approval codes and device requests after five minutes', function () {
    $request = startOwnerDevice($this);
    $this->travel(6)->minutes();
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'expired');
    $this->actingAs($this->owner)->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertUnprocessable();
});

it('cancels requests and revokes only their own uncertain exchange token', function () {
    $other = $this->owner->createToken('keep');
    $request = startOwnerDevice($this);
    $this->actingAs($this->owner)->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk();
    $result = $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertOk()->json();
    $this->postJson('/api/v1/mobile-owner-login/cancel', ['secret' => $request['secret']])->assertOk();
    expect(PersonalAccessToken::findToken($result['token']))->toBeNull();
    expect(PersonalAccessToken::findToken($other->plainTextToken))->not->toBeNull();
});

it('does not allow a second salon owner to replace an approval', function () {
    $other = User::factory()->create(['is_approved' => true]);
    $other->assignRole('owner');
    $request = startOwnerDevice($this);
    $this->actingAs($this->owner)->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk();
    $this->actingAs($other)->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertJsonPath('user.id', $this->owner->id);
});

it('rejects unapproved owners and rechecks approval after consent', function () {
    $request = startOwnerDevice($this);
    $this->owner->update(['is_approved' => false]);
    $this->actingAs($this->owner)->get('/mobile-owner-app-access')->assertForbidden();
    $this->postJson('/mobile-owner-app-access', ['code' => $request['code']])->assertForbidden();
    $this->owner->update(['is_approved' => true]);
    $this->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk();
    $this->owner->update(['is_approved' => false]);
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'denied')->assertJsonMissingPath('token');
    expect($this->owner->tokens()->count())->toBe(0);
});

it('separates owner and administrator device challenges', function () {
    $request = startOwnerDevice($this);
    $this->actingAs($this->admin)->postJson('/mobile-app-access', ['code' => $request['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-admin-login/poll', ['secret' => $request['secret']])->assertJsonPath('status', 'expired')->assertJsonMissingPath('token');
    $adminRequest = $this->postJson('/api/v1/mobile-admin-login/start')->assertCreated()->json();
    $this->actingAs($this->owner)->postJson('/mobile-owner-app-access', ['code' => $adminRequest['code']])->assertUnprocessable();
    $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $adminRequest['secret']])->assertJsonPath('status', 'expired');
});

it('allows owner sign-in without granting a paid subscription', function () {
    $request = startOwnerDevice($this);
    $this->actingAs($this->owner)->post('/mobile-owner-app-access', ['code' => $request['code']])->assertOk();
    $result = $this->postJson('/api/v1/mobile-owner-login/poll', ['secret' => $request['secret']])->assertJsonPath('user.role', 'owner')->json();
    $this->withToken($result['token'])->getJson('/api/v1/admin/overview')->assertForbidden();
    expect($this->owner->hasActiveSubscription())->toBeFalse();
});
