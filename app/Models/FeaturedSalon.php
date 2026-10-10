<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedSalon extends Model
{
    protected $fillable = [
        'salon_id',
        'starts_at',
        'ends_at',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];

    protected $dates = [
        'starts_at',
        'ends_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the salon that this featured entry belongs to.
     */
    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Scope to get active featured salons.
     * A featured salon is active if:
     * - active column is true
     * - starts_at is null or <= now
     * - ends_at is null or >= now
     */
    public function scopeActive($query)
    {
        return $query
            ->where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhereDate('ends_at', '>=', now());
            });
    }

    /**
     * Check if the featured entry has expired.
     */
    public function isExpired(): bool
    {
        if (!$this->ends_at) {
            return false; // No end date, never expires
        }

        return $this->ends_at <= now();
    }
}
