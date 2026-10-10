<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubscriptionPackage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'start_date',
        'end_date',
        'price',
        'duration_days',
        'max_salons',
        'max_staff',
        'max_services',
        'max_bookings_per_month',
        'analytics',
        'priority_support',
        'calendar',
        'features',
        'is_active',
        'is_popular',
        'order_column',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'max_salons' => 'integer',
        'max_staff' => 'integer',
        'max_services' => 'integer',
        'max_bookings_per_month' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'analytics' => 'boolean',
        'priority_support' => 'boolean',
        'calendar' => 'boolean',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
        'order_column' => 'integer',
        'features' => 'array', // Store as JSON
    ];

    // Relationships
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'package_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->where('is_popular', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column')->orderBy('price');
    }

    // Helper Methods
    public function isFree()
    {
        return $this->price == 0;
    }

    public function isAvailable(): bool
    {
        return $this->is_active && ! $this->trashed()
            && (! $this->start_date || $this->start_date->lte(today()))
            && (! $this->end_date || $this->end_date->gte(today()));
    }

    public function durationMonths()
    {
        return $this->duration_days / 30;
    }
}
