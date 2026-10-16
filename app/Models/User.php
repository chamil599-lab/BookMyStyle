<?php

namespace App\Models;

use App\RolesEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'phone',
        'location',
        'google_id',
        'avatar',
        'is_approved',
        'approved_at',
        'approved_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::updating(function (User $user): void {
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            if ($user->isDirty('password')) {
                $user->remember_token = Str::random(60);
            }
        });
        static::updated(function (User $user): void {
            if (! $user->wasChanged('password')) {
                return;
            }
            $user->tokens()->delete();
            if (config('session.driver') === 'database') {
                $sessions = DB::connection(config('session.connection'))
                    ->table(config('session.table', 'sessions'))->where('user_id', $user->id);
                if (request()->hasSession() && (int) Auth::guard('web')->id() === (int) $user->id) {
                    $sessions->where('id', '!=', request()->session()->getId());
                }
                $sessions->delete();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole([RolesEnum::ADMIN->value, RolesEnum::OWNER->value]);
        }

        return false;
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function salons()
    {
        return $this->hasMany(Salon::class, 'created_by');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    public function approve(): void
    {
        $this->update([
            'is_approved' => true,
            'approved_at' => now(),
            'approved_by' => Auth::id(),
        ]);
    }

    // app/Models/User.php
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->active()
            ->latest();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    public function hasActiveOrPendingSubscription(): bool
    {
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('status', 'active')
                        ->where('ends_at', '>', now());
                })->orWhere('status', 'pending');
            })
            ->exists();
    }

    public function canCreateMoreSalons(): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $package = $this->activeSubscription()->with('package')->first()?->package;
        if (! $package) {
            return false;
        }

        return $this->salons()->count() < $package->max_salons;
    }

    public function canAddMoreStaff($salon): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $package = $this->activeSubscription()->with('package')->first()?->package;
        if (! $package) {
            return false;
        }

        return $salon->staff()->count() < $package->max_staff;
    }

    public function canAddMoreServices($salon): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $package = $this->activeSubscription()->with('package')->first()?->package;
        if (! $package) {
            return false;
        }

        return $salon->services()->count() < $package->max_services;
    }

    public function canCreateMoreBookings(): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $package = $this->activeSubscription()->with('package')->first()?->package;
        if (! $package) {
            return false;
        }
        if ($package->max_bookings_per_month === null) {
            return true; // No limit
        }

        // Count bookings created this month for salons owned by this user
        $currentMonth = now()->startOfMonth();
        $currentMonthBookings = Booking::whereIn(
            'salon_id',
            $this->salons()->pluck('id')
        )
            ->where('created_at', '>=', $currentMonth)
            ->where('created_at', '<', $currentMonth->copy()->addMonth())
            ->count();

        return $currentMonthBookings < $package->max_bookings_per_month;
    }

    public function getMonthlyBookingsCount(): int
    {
        $currentMonth = now()->startOfMonth();

        return Booking::whereIn(
            'salon_id',
            $this->salons()->pluck('id')
        )
            ->where('created_at', '>=', $currentMonth)
            ->where('created_at', '<', $currentMonth->copy()->addMonth())
            ->count();
    }
}
