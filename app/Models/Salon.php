<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Salon extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'slug',
        'name',
        'owner_name',
        'location',
        'phone',
        'email',
        'open_time',
        'close_time',
        'description',
        'number_of_staff',
        'show_price',
        'facebook_page',
        'instagram_page',
        'tiktok_page',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'show_price' => 'boolean',
        'open_time' => 'datetime:H:i',
        'close_time' => 'datetime:H:i',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('gallery')
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->width(300)
        ->height(300)
        ->sharpen(10)
        ->nonQueued();

    $this->addMediaConversion('preview')
        ->width(800)
        ->height(600)
        ->nonQueued();
}

    // Helper methods to get media URLs with fallback to default image
    public function getCoverUrlAttribute(): string
    {
        $defaultImage = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80';
        $preview = $this->getFirstMediaUrl('cover', 'preview');
        $original = $this->getFirstMediaUrl('cover');

        return !empty($preview) ? $preview : (!empty($original) ? $original : $defaultImage);
    }

    public function getCoverThumbUrlAttribute(): string
    {
        $defaultImage = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80';
        $thumb = $this->getFirstMediaUrl('cover', 'thumb');
        $original = $this->getFirstMediaUrl('cover');

        return !empty($thumb) ? $thumb : (!empty($original) ? $original : $defaultImage);
    }

    public function getGalleryUrlsAttribute(): array
    {
        return $this->getMedia('gallery')->map(function ($media) {
            return [
                'id' => $media->id,
                'original' => $media->getUrl(),
                'preview' => $media->getUrl('preview'),
                'thumb' => $media->getUrl('thumb'),
            ];
        })->toArray();
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function services()
    {
        // salon_id was removed from services; services belong to an owner (created_by), not a specific salon
        return $this->hasMany(Service::class, 'created_by', 'created_by');
    }

    public function staff()
    {
        // Returns staff count for this salon
        // This can be extended if you have a dedicated staff/employee model
        // For now, returning a query that represents staff associations
        return $this->hasMany(TeamMember::class);
    }

    public function ratings()
    {
        return $this->hasMany(SalonRating::class);
    }

    public function getAverageRatingAttribute()
    {
        return $this->ratings()->avg('rating') ?? 0;
    }
}