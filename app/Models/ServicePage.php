<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePage extends Model
{
    use SoftDeletes;

    protected $table = 'service_pages';

    protected $fillable = [
        'src_url',
        'title',
        'slug',
        'description',
        'price',
        'duration',
        'image',
        'features',
        'category_id',
        'category_name',
        'category_color',
        'created_by',
        'updated_by',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
