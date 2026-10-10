<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'amount_paid',
        'payment_method',
        'transaction_id',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
        'features_snapshot',
        'renewed_from',
        'is_trial',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'features_snapshot' => 'array',
        'is_trial' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPackage::class, 'package_id');
    }

    public function renewedFrom(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'renewed_from');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('ends_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'expired')
              ->orWhere('ends_at', '<=', now());
        });
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    // Methods
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->ends_at <= now();
    }

    public function daysRemaining(): int
    {
        if (!$this->ends_at) return 0;
        return now()->diffInDays($this->ends_at, false);
    }
}