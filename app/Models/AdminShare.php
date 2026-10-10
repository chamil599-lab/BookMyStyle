<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminShare extends Model
{
    protected $fillable = [
        'salon_id',
        'amount',
        'paid',
        'paid_date',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid' => 'decimal:2',
        'paid_date' => 'date',
    ];

    public function salon(): BelongsTo
    {
        return $this->belongsTo(Salon::class);
    }

    /**
     * Remaining balance (amount - paid)
     */
    public function getBalanceAttribute(): float
    {
        return $this->amount - $this->paid;
    }
}