<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ImageCycle extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'image_cycles';

    protected $fillable = [
        'title',
        'description',
        'alt',
        'src_url',
        'upload_path',
        'order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'order' => 'integer',
        'status' => 'integer',
    ];

    // Return the best available image URL: prefer src_url, then uploaded file path
    public function getImageUrlAttribute()
    {
        if ($this->src_url) {
            return $this->src_url;
        }

        if ($this->upload_path) {
            // Use Storage facade to generate a URL when stored on disk
            return Storage::url($this->upload_path);
        }

        return null;
    }
}
