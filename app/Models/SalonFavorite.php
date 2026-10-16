<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalonFavorite extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'salon_id',
    ];

    /**
     * Get the user that favorited the salon
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the salon that was favorited
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
}
