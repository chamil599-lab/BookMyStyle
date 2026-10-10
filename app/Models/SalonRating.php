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

    protected $hidden = ['email'];

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
