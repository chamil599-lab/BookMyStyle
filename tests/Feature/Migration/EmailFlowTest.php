<?php

use App\Mail\OwnerRegistered;
use App\Mail\UserApproved;
use App\Mail\UserRejected;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['mail.default' => 'array']);
    app('mail.manager')->purge();
    Role::findOrCreate('customer', 'web');
    $this->messages = fn () => Mail::mailer()->getSymfonyTransport()->messages();
});

it('renders and delivers a real verification notification into the isolated transport and accepts its link', function () {
    $user = app(AccountService::class)->register(['name' => 'Email fixture', 'email' => 'mail-flow@example.test',
        'password' => 'fixture-password', 'password_confirmation' => 'fixture-password']);
    expect(($this->messages)()->map(fn ($sent) => $sent->getOriginalMessage()->getSubject())->all())->toBe(['Verify Email Address']);
    $message = ($this->messages)()->last()->getOriginalMessage();
    expect($message->getTo()[0]->getAddress())->toBe($user->email);
    preg_match('/href="([^"]*verify-email\/[^\"]+)"/', $message->getHtmlBody(), $matches);
    expect($matches)->toHaveCount(2);
    $url = html_entity_decode($matches[1], ENT_QUOTES);
    $this->actingAs($user)->get($url)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('renders a password-reset email whose link completes the native reset flow', function () {
    $user = User::factory()->create();
    expect(Password::sendResetLink(['email' => $user->email]))->toBe(Password::RESET_LINK_SENT);
    $message = ($this->messages)()->last()->getOriginalMessage();
    preg_match('/href="([^"]*reset-password\/[^\"]+)"/', $message->getHtmlBody(), $matches);
    expect($matches)->toHaveCount(2)->and($message->getTo()[0]->getAddress())->toBe($user->email);
    $url = html_entity_decode($matches[1], ENT_QUOTES);
    $this->get($url)->assertOk();
    $token = basename(parse_url($url, PHP_URL_PATH));
    Volt::test('pages.auth.reset-password', ['token' => $token])->set('email', $user->email)
        ->set('password', 'new-fixture-password')->set('password_confirmation', 'new-fixture-password')
        ->call('resetPassword')->assertHasNoErrors()->assertRedirect(route('login'));
    expect(Hash::check('new-fixture-password', $user->fresh()->password))->toBeTrue();
});

it('renders each account status email to the intended recipient without a queue worker', function () {
    $user = User::factory()->create();
    foreach ([OwnerRegistered::class, UserApproved::class, UserRejected::class] as $mail) {
        Mail::to($user->email)->send(new $mail($user));
        $message = ($this->messages)()->last()->getOriginalMessage();
        expect($message->getTo()[0]->getAddress())->toBe($user->email)
            ->and($message->getHtmlBody())->toContain('BookMyStyle');
    }
    expect(($this->messages)())->toHaveCount(3);
    expect((new UserApproved($user))->render())->toContain(route('dashboard'))
        ->not->toContain('https://bookmystyle.limach.lk/admin');
});
