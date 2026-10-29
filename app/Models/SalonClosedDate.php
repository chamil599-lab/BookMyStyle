<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalonClosedDate extends Model
{
    protected $fillable = ['salon_id', 'date', 'reason'];
}
