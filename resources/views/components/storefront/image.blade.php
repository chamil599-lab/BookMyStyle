@props(['src' => null, 'alt' => '', 'loading' => 'lazy'])
@php($fallback = '/images/salon-placeholder.svg')
<img src="{{ \App\Support\PublicUrl::image($src) ?: $fallback }}"
     alt="{{ $alt }}" loading="{{ $loading }}"
     x-data x-init="if ($el.complete && ! $el.naturalWidth) $el.src = @js($fallback)"
     x-on:error.once="$el.src = @js($fallback)"
     {{ $attributes }}>
