@props(['title' => 'BookMyStyle', 'description' => 'Discover salons and beauty studios across Sri Lanka. Explore services, read reviews, and request your next appointment.'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — BookMyStyle</title>
    <meta name="description" content="{{ $description }}">
    @php($canonical = rtrim(config('app.url'), '/').'/'.ltrim(request()->path(), '/'))
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:title" content="{{ $title }} — BookMyStyle">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:type" content="website">
    <link rel="icon" href="{{ asset('images/site-icon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Unbounded:wght@400;600;700;800&family=Libre+Baskerville:ital,wght@0,400;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>try { document.documentElement.classList.toggle('dark', (localStorage.getItem('appearance') || 'system') === 'dark' || (localStorage.getItem('appearance') !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches)); } catch (_) {}</script>
    @vite('resources/css/storefront.css')
    @livewireStyles
</head>
<body @class(['home-luxury' => request()->routeIs('home'), 'salon-detail' => request()->routeIs('salon.show'), 'public-luxury' => request()->routeIs('salons', 'trending', 'popular', 'services', 'deals', 'about', 'privacy', 'terms')])>
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 button">Skip to content</a>
<header class="sticky top-0 z-40 bg-[#1e1710] text-[#fff3e5]" x-data="{ open: false }" @click.outside="open = false">
    <div class="shell storefront-nav-inner flex min-h-20 items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="storefront-brand flex items-center gap-3" aria-label="BookMyStyle home">
            <x-app-logo-icon class="storefront-brand-mark" />
            <span><span class="brand block text-sm font-bold">BookMyStyle</span><span class="eyebrow block mt-1">Beauty Booking</span></span>
        </a>
        <nav aria-label="Main navigation" class="storefront-desktop-links hidden lg:flex text-xs font-semibold">
            @foreach (['home' => 'Home', 'services' => 'Services', 'deals' => 'Deals', 'about' => 'About'] as $route => $label)
                <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="storefront-nav-actions flex items-center gap-3 text-xs">
            <button type="button" aria-label="Toggle color theme" @click="const dark = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('appearance', dark ? 'dark' : 'light') } catch (_) {}" class="storefront-icon-button">◐</button>
            @auth
                @if(app(\App\Services\AccountNotifications::class)->canAccess(auth()->user()?->fresh()))
                    @livewire('notification-bell')
                @endif
                <a href="{{ route('dashboard') }}" class="storefront-account-link hidden sm:block">My account</a>
                <form method="POST" action="{{ route('logout') }}" class="hidden lg:block">@csrf<button type="submit" class="storefront-signout">Log out</button></form>
            @else
                <a href="{{ route('login') }}" class="storefront-signin hidden sm:block">Sign in</a>
                <a href="{{ route('register') }}" class="button storefront-join hidden sm:inline-flex">Join us</a>
            @endauth
            <button type="button" class="storefront-menu-toggle lg:hidden" x-ref="menuToggle" @click="open = !open" :aria-expanded="open" aria-controls="mobile-nav">Menu</button>
        </div>
    </div>
    <nav id="mobile-nav" aria-label="Mobile navigation" x-cloak x-show="open" @keydown.escape.window="if (open) { open = false; $refs.menuToggle.focus() }" class="storefront-mobile-links lg:hidden shell border-t border-white/20 py-5 grid grid-cols-2 gap-5 text-sm">
        @foreach (['home' => 'Home', 'services' => 'Services', 'deals' => 'Deals', 'about' => 'About'] as $route => $label)
            <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
        @auth
            <a href="{{ route('dashboard') }}">My account</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out</button></form>
        @else
            <a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create account</a>
        @endauth
    </nav>
</header>
<main id="main">{{ $slot }}</main>
<footer class="mt-16 bg-[#1e1710] text-[#fff3e5]">
    <div class="shell grid gap-10 py-14 md:grid-cols-3">
        <div><div class="flex items-center gap-3 mb-4"><x-app-logo-icon class="size-12" /><p class="brand text-xl font-bold">BookMyStyle</p></div><p class="text-sm leading-7 text-white/70 max-w-sm">Your next great style starts here. Discover salons and beauty studios across Sri Lanka.</p><a href="{{ route('register') }}" class="inline-block mt-5 text-[#e07b47]">List your salon →</a></div>
        <nav aria-label="Footer navigation" class="grid gap-3 text-sm"><p class="eyebrow mb-2">Explore</p><a href="{{ route('salons') }}">Salons</a><a href="{{ route('trending') }}">Trending salons</a><a href="{{ route('popular') }}">Popular salons</a><a href="{{ route('services') }}">Services</a><a href="{{ route('deals') }}">Deals</a></nav>
        <nav aria-label="Support navigation" class="grid gap-3 text-sm"><p class="eyebrow mb-2">Support</p><a href="{{ route('about') }}">About us</a><a href="{{ route('terms') }}">Terms and conditions</a><a href="{{ route('privacy') }}">Privacy policy</a><a href="mailto:info@bookmystyle.com">info@bookmystyle.com</a></nav>
    </div>
    <div class="shell border-t border-white/15 py-6 text-xs text-white/60 flex flex-wrap justify-between gap-4"><p>© {{ date('Y') }} BookMyStyle. All rights reserved.</p><a href="https://limach.lk" target="_blank" rel="noopener noreferrer">Powered by Limach Web Solutions</a></div>
</footer>
@livewireScripts
</body>
</html>
