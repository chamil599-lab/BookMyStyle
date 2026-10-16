<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

it('does not accept bearer tokens in URL parameters', function () {
    $user = User::factory()->create();
    $token = $user->createToken('fixture')->plainTextToken;
    $this->getJson('/api/auth/me?'.http_build_query(['token' => $token, 'access_token' => $token]))->assertUnauthorized();
});

it('does not let the alternate authorization header override the standard header', function () {
    $user = User::factory()->create();
    $token = $user->createToken('fixture')->plainTextToken;
    $this->withHeaders(['Authorization' => 'Bearer invalid-fixture', 'X-Authorization' => 'Bearer '.$token])
        ->getJson('/api/auth/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->withHeaders(['Authorization' => 'Bearer '.$token, 'X-Authorization' => 'Bearer invalid-fixture'])
        ->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.id', $user->id);
});

it('accepts the supported alternate bearer header without leaking credentials in identity output', function () {
    $user = User::factory()->create();
    $token = $user->createToken('fixture')->plainTextToken;
    $this->withHeader('X-Authorization', 'Bearer '.$token)->getJson('/api/auth/me')->assertOk()
        ->assertJsonPath('user.id', $user->id)->assertJsonMissingPath('user.password')
        ->assertJsonMissingPath('user.remember_token')->assertDontSee($token, false);
});

it('does not load browser session cookies to authenticate the bearer API', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $id = str_repeat('a', 40);
    DB::table('sessions')->insert(['id' => $id, 'user_id' => $user->id, 'last_activity' => now()->timestamp,
        'payload' => base64_encode(serialize([app('auth')->guard('web')->getName() => $user->id]))]);
    $this->app['auth']->forgetGuards();
    $this->withCookie(config('session.cookie'), $id)->getJson('/api/auth/me')->assertUnauthorized();
});

it('does not grant CORS access to an unapproved origin', function () {
    config(['cors.allowed_origins' => ['https://approved.example'], 'cors.supports_credentials' => false]);
    $response = $this->withHeaders(['Origin' => 'https://attacker.example',
        'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'Authorization,Content-Type'])
        ->options('/api/auth/login');
    expect($response->headers->get('Access-Control-Allow-Origin'))->not->toBe('https://attacker.example')->not->toBe('*');
    $response->assertHeaderMissing('Access-Control-Allow-Credentials');
});

it('permits explicitly approved CORS origins without credentialed cookies', function () {
    config(['cors.allowed_origins' => ['https://approved.example'], 'cors.supports_credentials' => false]);
    $this->withHeaders(['Origin' => 'https://approved.example', 'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'Authorization,Content-Type'])->options('/api/auth/login')
        ->assertHeader('Access-Control-Allow-Origin', 'https://approved.example')->assertHeaderMissing('Access-Control-Allow-Credentials');
});
