<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class PublicUrl
{
    public static function link(?string $value): ?string
    {
        if (! $value || preg_match('/[\x00-\x20\\\\]/', $value)) {
            return null;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $value : null;
    }

    public static function image(?string $value): ?string
    {
        if ($url = self::link($value)) {
            return $url;
        }
        if (! $value || str_starts_with($value, '/') || str_contains($value, ':') || str_contains($value, '..') || preg_match('/[\x00-\x20\\\\]/', $value)) {
            return null;
        }

        return self::link(Storage::disk('public')->url($value));
    }
}
