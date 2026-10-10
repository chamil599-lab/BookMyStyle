<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'salon_id',
        'name',
        'email',
        'phone',
        'service_id',
        'date',
        'time',
        'status',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    public static function statusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    /**
     * Check if this is a guest booking
     */
    public function isGuestBooking(): bool
    {
        return is_null($this->user_id);
    }

    /**
     * Scope to get only user bookings
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get bookings connected to a customer account.
     */
    public function scopeForCustomer($query, User $user)
    {
        return $query->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere(function ($query) use ($user) {
                    $query->whereNotNull('email')
                        ->where('email', $user->email);
                });
        });
    }

    /**
     * Scope to get upcoming bookings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
    }

    /**
     * Scope to get past bookings
     */
    public function scopePast($query)
    {
        return $query->where('date', '<', now()->toDateString())
                    ->orderBy('date', 'desc')
                    ->orderBy('time', 'desc');
    }
}
