<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-semibold">Your account</h1></x-slot>
    <div class="max-w-3xl mx-auto p-6 space-y-4">
        <h2 class="text-xl font-semibold">Welcome, {{ auth()->user()->name }}</h2>
        @if (auth()->user()->hasRole('owner') && ! auth()->user()->is_approved)
            <p role="status">Your salon owner account is awaiting admin approval. You can manage your profile and browse salons while you wait.</p>
        @endif
        <a class="underline" href="{{ route('profile') }}">Manage your profile and password</a>
        <p><a class="underline" href="{{ route('home') }}">Browse BookMyStyle</a></p>
    </div>
</x-app-layout>
