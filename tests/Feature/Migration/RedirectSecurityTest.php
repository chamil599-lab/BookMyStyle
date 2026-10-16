<?php

use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    Role::findOrCreate('customer', 'web');
    $this->user = User::factory()->create();
    $this->user->assignRole('customer');
});

it('discards unsafe saved login destinations', function ($destination) {
    $this->withSession(['url.intended' => $destination])->get('/login')->assertOk();
    Volt::test('pages.auth.login')->set('form.email', $this->user->email)->set('form.password', 'password')
        ->call('login')->assertHasNoErrors()->assertRedirect('/dashboard');
})->with(['https://attacker.example/phish', '//attacker.example/phish', '/\\attacker.example', 'javascript:alert(1)', 'http://localhost:9999/phish']);

it('preserves a same-origin saved login destination', function () {
    $this->withSession(['url.intended' => '/dashboard/favorites']);
    Volt::test('pages.auth.login')->set('form.email', $this->user->email)->set('form.password', 'password')
        ->call('login')->assertHasNoErrors()->assertRedirect('/dashboard/favorites');
});

it('blocks external Referer back redirects after a favorite action', function () {
    $salon = Salon::create(['name' => 'Redirect fixture', 'owner_name' => 'Fixture', 'created_by' => $this->user->id, 'updated_by' => $this->user->id]);
    $this->actingAs($this->user)->withHeader('Referer', 'https://attacker.example/phish')
        ->delete('/salons/'.$salon->id.'/favorite')->assertRedirect('/');
});

it('preserves same-origin Referer back redirects', function () {
    $destination = url('/dashboard/favorites');
    $this->actingAs($this->user)->withHeader('Referer', $destination)
        ->delete('/salons/999999/favorite')->assertRedirect($destination);
});
