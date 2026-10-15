@props(['title' => 'Your account'])
<x-storefront.layout :title="$title" description="Manage your appointments and saved salons.">
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
</x-storefront.layout>
