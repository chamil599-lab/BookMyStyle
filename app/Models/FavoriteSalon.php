<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteSalon extends Model
{
    protected $fillable = [
        'user_id',
        'salon_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
