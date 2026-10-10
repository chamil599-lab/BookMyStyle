@props(['title' => 'Your account'])
<x-storefront.layout :title="$title" description="Manage your appointments and saved salons.">
    <div class="shell py-10 lg:py-16">
        <p class="eyebrow">Your account</p>
        <h1 class="section-title mt-3 mb-6">{{ $title }}</h1>
        <nav aria-label="Account navigation" class="flex flex-wrap gap-2 mb-8 text-sm">
            @foreach (['dashboard' => 'Overview', 'customer.upcoming' => 'Upcoming', 'customer.history' => 'History', 'customer.favorites' => 'Favorites', 'customer.notifications' => 'Notifications', 'customer.profile' => 'Profile', 'customer.password' => 'Password'] as $name => $label)
                <a href="{{ route($name) }}" class="panel px-4 py-3" @if(request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
        @if(session('status'))<p role="status" class="panel p-4 mb-6">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="panel p-4 mb-6">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        {{ $slot }}
    </div>
</x-storefront.layout>
