<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TeamMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'role',
        'bio',
        'src_url',
        'upload_path',
        'order',
        'status',
    ];

    protected $casts = [
        'order' => 'integer',
        'status' => 'boolean',
    ];

    // Accessor for image URL: prefer external src_url, fallback to storage url
    public function getImageUrlAttribute()
    {
        if ($this->src_url) {
            return $this->src_url;
        }

        if ($this->upload_path) {
            return Storage::url($this->upload_path);
        }

        return null;
    }
}
