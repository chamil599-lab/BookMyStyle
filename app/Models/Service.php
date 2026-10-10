<?php

namespace App\Models;

use App\Enums\Enums\ServiceEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'department_id',
        'category_id',
        'price',
        'duration',
        'status',
        'created_by',
        'updated_by',
    ];

    public function scopeForOwner(Builder $query)
    {
        return $query->where('created_by', Auth::user()->id);
    }

    public function scopePublished(Builder $query)
    {
        return $query->where('status', ServiceEnum::Published);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }


    public function getLowestPriceAttribute()
    {
        return Service::min('price');
    }
}
