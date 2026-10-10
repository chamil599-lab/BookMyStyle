<?php

namespace App\Helpers;

use App\Models\Salon;
use App\Models\Service;

class SalonHelper
{
    public static function formatSalonWithMedia($salon, $includeServices = false)
    {
        // Get cover URLs with proper fallback handling
        $coverPreview = $salon->getFirstMediaUrl('cover', 'preview');
        $coverOriginal = $salon->getFirstMediaUrl('cover');
        $coverThumb = $salon->getFirstMediaUrl('cover', 'thumb');

        $defaultImage = 'https://images.unsplash.com/photo-1562322140-8baeececf3df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1469&q=80';

        // Use preview conversion, fallback to original, then default
        $coverUrl = !empty($coverPreview) ? $coverPreview : (!empty($coverOriginal) ? $coverOriginal : $defaultImage);
        $coverThumbUrl = !empty($coverThumb) ? $coverThumb : (!empty($coverOriginal) ? $coverOriginal : $defaultImage);

        $data = [
            'id' => $salon->id,
            'name' => $salon->name,
            'owner_name' => $salon->owner_name,
            'description' => $salon->description,
            'location' => $salon->location,
            'phone' => $salon->phone,
            'email' => $salon->email,
            'open_time' => $salon->open_time?->format('H:i') ?? $salon->open_time,
            'close_time' => $salon->close_time?->format('H:i') ?? $salon->close_time,
            'number_of_staff' => $salon->number_of_staff,
            'show_price' => (bool) $salon->show_price,
            'facebook_page' => $salon->facebook_page,
            'instagram_page' => $salon->instagram_page,
            'tiktok_page' => $salon->tiktok_page,
            'rating' => $salon->average_rating,

            // Cover URLs with fallback to default image
            'cover_url' => $coverUrl,
            'cover_thumb_url' => $coverThumbUrl,

            // Gallery - use original as fallback for each conversion
            'gallery' => $salon->getMedia('gallery')->map(function ($media) {
                $original = $media->getUrl();
                return [
                    'id' => $media->id,
                    'original' => $original,
                    'preview' => $media->getUrl('preview') ?: $original,
                    'thumb' => $media->getUrl('thumb') ?: $original,
                ];
            })->toArray(),
        ];

        if ($includeServices) {
            $services = Service::where('created_by', $salon->created_by)
                ->orWhere('updated_by', $salon->created_by)
                ->with('department')
                ->get();
            $data['services'] = $services;
        }

        return $data;
    }
}