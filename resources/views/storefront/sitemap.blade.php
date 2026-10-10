{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach(['home', 'salons', 'services', 'deals', 'about', 'trending', 'popular', 'terms', 'privacy'] as $route)
        <url><loc>{{ rtrim(config('app.url'), '/').route($route, [], false) }}</loc></url>
    @endforeach
    @foreach($salons as $salon)
        <url><loc>{{ rtrim(config('app.url'), '/').route('salon.show', $salon->id, false) }}</loc><lastmod>{{ $salon->updated_at->toAtomString() }}</lastmod></url>
    @endforeach
</urlset>
