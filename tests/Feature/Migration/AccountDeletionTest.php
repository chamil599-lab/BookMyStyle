<?php

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\AccountDeletion;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['admin', 'owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->deletion = app(AccountDeletion::class);
});

function deletionCredentials(User $user): void
{
    $user->createToken('fixture');
    $user->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'fixture', 'data' => ['message' => 'Fixture']]);
    Password::createToken($user);
    DB::table('sessions')->insert(['id' => 'fixture-'.$user->id, 'user_id' => $user->id, 'payload' => 'fixture', 'last_activity' => time()]);
}

it('cleans account credentials and preserves other accounts', function () {
    config(['session.driver' => 'database', 'session.connection' => null]);
    deletionCredentials($this->customer);
    deletionCredentials($this->admin);
    expect($this->deletion->selfDelete($this->customer, 'password'))->toBeTrue();
    expect($this->customer->fresh())->toBeNull()
        ->and(DB::table('personal_access_tokens')->count())->toBe(1)
        ->and(DB::table('notifications')->count())->toBe(1)
        ->and(DB::table('password_reset_tokens')->count())->toBe(1)
        ->and(DB::table('sessions')->count())->toBe(1)
        ->and($this->admin->fresh())->not->toBeNull();
});

it('blocks stale self-deletion after promotion to owner', function () {
    $this->customer->load('roles');
    User::findOrFail($this->customer->id)->syncRoles(['owner']);
    $this->actingAs($this->customer);
    Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertForbidden();
    expect($this->customer->fresh())->not->toBeNull();
});

it('checks the current password rather than a stale actor password', function () {
    User::findOrFail($this->customer->id)->update(['password' => 'Changed-Fixture-Password-123!']);
    expect(fn () => $this->deletion->selfDelete($this->customer, 'password'))->toThrow(ValidationException::class);
    expect($this->customer->fresh())->not->toBeNull();
});

it('blocks former owners with salon records from deleting through the customer form', function () {
    Salon::create(['name' => 'Fixture', 'owner_name' => 'Owner', 'created_by' => $this->customer->id, 'updated_by' => $this->customer->id]);
    expect(fn () => $this->deletion->selfDelete($this->customer, 'password'))->toThrow(ValidationException::class);
    expect($this->customer->fresh())->not->toBeNull()
        ->and(Gate::forUser($this->admin)->allows('delete', $this->customer))->toBeFalse();
});

it('blocks stale admin-target policy decisions and revoked administrative actors', function () {
    $target = $this->customer->load('roles');
    User::findOrFail($target->id)->syncRoles(['admin']);
    expect(Gate::forUser($this->admin)->allows('update', $target))->toBeFalse()
        ->and(Gate::forUser($this->admin)->allows('delete', $target))->toBeFalse();
    User::findOrFail($target->id)->syncRoles(['customer']);
    $this->admin->load('roles');
    User::findOrFail($this->admin->id)->syncRoles(['customer']);
    expect(fn () => $this->deletion->managedDelete($this->admin, $target))->toThrow(AuthorizationException::class);
    expect($target->fresh())->not->toBeNull();
});

it('rolls credential cleanup back if account deletion fails', function () {
    config(['session.driver' => 'database', 'session.connection' => null]);
    deletionCredentials($this->customer);
    Event::listen('eloquent.deleting: '.User::class, function (User $record) {
        if ($record->id === $this->customer->id) {
            throw new RuntimeException('Synthetic deletion failure');
        }
    });
    expect(fn () => $this->deletion->selfDelete($this->customer, 'password'))->toThrow(RuntimeException::class, 'Synthetic deletion failure');
    expect($this->customer->fresh())->not->toBeNull()
        ->and(DB::table('personal_access_tokens')->count())->toBe(1)
        ->and(DB::table('notifications')->count())->toBe(1)
        ->and(DB::table('password_reset_tokens')->count())->toBe(1)
        ->and(DB::table('sessions')->count())->toBe(1);
});

it('uses shared cleanup for the Filament table delete action', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->customer->update(['is_approved' => true]);
    $this->customer->createToken('fixture');
    $this->actingAs($this->admin);
    Livewire::test(ListUsers::class)
        ->callTableAction('delete', $this->customer)->assertHasNoActionErrors();
    expect($this->customer->fresh())->toBeNull()->and(DB::table('personal_access_tokens')->count())->toBe(0);
});

it('uses shared cleanup for the Filament edit-page delete action', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->customer->createToken('fixture');
    $this->actingAs($this->admin);
    Livewire::test(EditUser::class, ['record' => $this->customer->id])
        ->callAction('delete')->assertHasNoActionErrors();
    expect($this->customer->fresh())->toBeNull()->and(DB::table('personal_access_tokens')->count())->toBe(0);
});

it('deletes permitted bulk accounts while preserving linked owner records and credentials', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $second = User::factory()->create();
    $second->assignRole('customer');
    $linked = User::factory()->create(['is_approved' => true]);
    $linked->assignRole('owner');
    $linked->createToken('protected');
    Salon::create(['name' => 'Protected fixture', 'owner_name' => 'Owner', 'created_by' => $linked->id, 'updated_by' => $linked->id]);
    $this->customer->createToken('fixture');
    $second->createToken('fixture');
    $this->actingAs($this->admin);
    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', [$this->customer, $second, $linked])->assertHasNoActionErrors();
    expect($this->customer->fresh())->toBeNull()->and($second->fresh())->toBeNull()
        ->and($linked->fresh())->not->toBeNull()->and(Salon::count())->toBe(1)
        ->and(DB::table('personal_access_tokens')->count())->toBe(1);
});

it('preserves customer booking records with an unlinked user reference', function () {
    $salon = Salon::create(['name' => 'Other salon', 'owner_name' => 'Owner', 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id]);
    $department = Department::create(['name' => 'Fixture', 'slug' => 'fixture']);
    $category = Category::create(['name' => 'Fixture', 'department_id' => $department->id]);
    $service = Service::create(['title' => 'Fixture', 'slug' => 'fixture', 'description' => 'Fixture', 'department_id' => $department->id,
        'category_id' => $category->id, 'created_by' => $this->admin->id, 'updated_by' => $this->admin->id, 'price' => 100, 'duration' => 30, 'status' => 'published']);
    $booking = Booking::create(['user_id' => $this->customer->id, 'salon_id' => $salon->id, 'service_id' => $service->id,
        'name' => 'Fixture', 'email' => $this->customer->email, 'phone' => '0700000000', 'date' => today()->addDay(), 'time' => '10:00', 'status' => 'approved']);
    $this->deletion->selfDelete($this->customer, 'password');
    expect($booking->fresh())->not->toBeNull()->and($booking->fresh()->user_id)->toBeNull()
        ->and($booking->fresh()->email)->toBe($this->customer->email)->and($salon->fresh())->not->toBeNull();
});

it('preserves subscription history even if an owner role has been removed', function () {
    $package = SubscriptionPackage::create(['name' => 'Fixture', 'slug' => 'fixture', 'price' => 100, 'duration_days' => 30,
        'max_salons' => 1, 'max_staff' => 1, 'max_services' => 1]);
    $paid = UserSubscription::create(['user_id' => $this->customer->id, 'package_id' => $package->id,
        'amount_paid' => 100, 'status' => 'expired', 'starts_at' => now()->subDays(40), 'ends_at' => now()->subDays(10)]);
    expect(fn () => $this->deletion->selfDelete($this->customer, 'password'))->toThrow(ValidationException::class);
    expect(Gate::forUser($this->admin)->allows('delete', $this->customer))->toBeFalse()
        ->and($paid->fresh())->not->toBeNull()->and($this->customer->fresh())->not->toBeNull();
});
