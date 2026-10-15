@props(['title' => 'Your account'])
<x-storefront.layout :title="$title" description="Manage your appointments and saved salons." :customer-app="true">
    <div class="customer-app-shell" x-data="{ accountOpen: false }" @keydown.escape.window="if (accountOpen) { accountOpen = false; $refs.accountToggle.focus() }">
        <header class="customer-app-header">
            <a href="{{ route('dashboard') }}" class="customer-app-brand" aria-label="BookMyStyle account home"><x-app-logo-icon /><span>BookMyStyle<small>Your beauty space</small></span></a>
            <div class="customer-app-header-actions">
                @livewire('notification-bell', key('customer-app-notifications'))
                <button type="button" class="customer-avatar" x-ref="accountToggle" @click="accountOpen = !accountOpen; if (accountOpen) $nextTick(() => requestAnimationFrame(() => $refs.accountHeading.focus()))" :aria-expanded="accountOpen" aria-controls="customer-app-account" aria-label="Open account menu">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</button>
            </div>
        </header>
        <button type="button" class="customer-app-scrim" x-show="accountOpen" x-cloak @click="accountOpen = false; $refs.accountToggle.focus()" aria-label="Close account menu"></button>
        <section id="customer-app-account" class="customer-app-account" x-show="accountOpen" x-cloak role="dialog" aria-modal="true" aria-labelledby="customer-app-account-heading"
            @keydown.tab="const items = [...$el.querySelectorAll('a[href], button:not([disabled])')]; const first = items[0]; const last = items[items.length - 1]; if ($event.shiftKey && (document.activeElement === first || document.activeElement === $refs.accountHeading)) { $event.preventDefault(); last.focus(); } else if (!$event.shiftKey && document.activeElement === last) { $event.preventDefault(); first.focus(); }">
            <div class="customer-app-account-heading"><h2 id="customer-app-account-heading" tabindex="-1" x-ref="accountHeading">{{ auth()->user()->name }}</h2><button type="button" @click="accountOpen = false; $refs.accountToggle.focus()" aria-label="Close account menu">&times;</button></div>
            <nav aria-label="Mobile account settings">
                <a href="{{ route('customer.profile') }}">Your profile <span aria-hidden="true">&nearr;</span></a>
                <a href="{{ route('customer.password') }}">Password &amp; security <span aria-hidden="true">&nearr;</span></a>
                <a href="{{ route('customer.history') }}">Booking history <span aria-hidden="true">&nearr;</span></a>
                <a href="{{ route('customer.notifications') }}">Notifications <span aria-hidden="true">&nearr;</span></a>
                <a href="{{ route('about') }}">About BookMyStyle <span aria-hidden="true">&nearr;</span></a>
                <a href="{{ route('privacy') }}">Privacy policy <span aria-hidden="true">&nearr;</span></a>
                <button type="button" @click="const dark = document.documentElement.classList.toggle('dark'); try { localStorage.setItem('appearance', dark ? 'dark' : 'light') } catch (_) {}">Switch light / dark theme <span aria-hidden="true">&#9680;</span></button>
            </nav>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Log out <span aria-hidden="true">&rarr;</span></button></form>
        </section>
    <div class="shell customer-workspace">
        <header class="customer-page-heading"><div><p class="eyebrow">BookMyStyle / Your space</p><h1>{{ $title }}</h1></div><a class="button" href="{{ route('home') }}#salons-section">Find your next salon <span aria-hidden="true">&nearr;</span></a></header>
        <div class="customer-workspace-grid">
            <aside class="customer-sidebar">
                <div class="customer-identity"><span class="customer-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><div><p>{{ auth()->user()->name }}</p><span>Your beauty journal</span></div></div>
                <nav aria-label="Account navigation">
                    @foreach (['dashboard' => 'Overview', 'customer.upcoming' => 'Upcoming', 'customer.history' => 'History', 'customer.favorites' => 'Favorites', 'customer.notifications' => 'Notifications', 'customer.profile' => 'Profile', 'customer.password' => 'Password'] as $name => $label)
                        <a href="{{ route($name) }}" @if(request()->routeIs($name)) aria-current="page" @endif><span>{{ $label }}</span><span aria-hidden="true">&nearr;</span></a>
                    @endforeach
                </nav>
                <p class="customer-sidebar-note">A little self-care.<br>A space that is yours.</p>
            </aside>
            <div class="customer-main">
                @if(session('status'))<p role="status" class="panel p-4 mb-6">{{ session('status') }}</p>@endif
                @if($errors->any())<div role="alert" class="panel p-4 mb-6">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                {{ $slot }}
            </div>
        </div>
    </div>
    <nav class="customer-app-bottom" aria-label="Mobile app navigation">
        @foreach([['dashboard', 'Home', 'heroicon-o-home'], ['customer.upcoming', 'Bookings', 'heroicon-o-calendar-days'], ['home', 'Discover', 'heroicon-o-magnifying-glass'], ['customer.favorites', 'Saved', 'heroicon-o-heart']] as [$route, $label, $icon])
            <a href="{{ route($route) }}{{ $route === 'home' ? '#salons-section' : '' }}" @if(request()->routeIs($route)) aria-current="page" @elseif($route === 'customer.upcoming' && request()->routeIs('customer.history')) aria-current="true" @endif><span class="customer-app-tab-icon"><x-filament::icon :icon="$icon" /></span><span>{{ $label }}</span></a>
        @endforeach
        <button type="button" @click="accountOpen = !accountOpen; if (accountOpen) $nextTick(() => requestAnimationFrame(() => $refs.accountHeading.focus()))" :aria-expanded="accountOpen" aria-controls="customer-app-account"><span class="customer-app-tab-icon"><x-filament::icon icon="heroicon-o-user-circle" /></span><span>Account</span></button>
    </nav>
    </div>
</x-storefront.layout>
