<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'salon_id',
        'email',
        'rating',
        'review',
    ];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
