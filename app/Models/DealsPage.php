<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dealspage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dealspages';

    protected $fillable = [
        'src_url',
        'title',
        'slug',
        'category',
        'salon',
        'original_price',
        'discounted_price',
        'discount',
        'image',
        'badge',
        'badge_color',
        'description',
        'valid_until',
        'features',
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
        'valid_until' => 'date',
    ];
}
