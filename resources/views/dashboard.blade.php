@props([])

<x-layouts.app>
    <div class="container mx-auto py-12">
        <div class="max-w-3xl mx-auto bg-white shadow rounded-lg p-8">
            <h1 class="text-2xl font-semibold mb-4">Dashboard</h1>

            @if (session('message'))
                <div class="mb-4 p-4 rounded bg-green-50 border border-green-200 text-green-800">
                    {{ session('message') }}
                </div>
            @endif

            @auth
                @if (auth()->user()->hasRole('owner'))
                    @if (! auth()->user()->isApproved())
                        <div class="p-6 bg-yellow-50 border border-yellow-200 rounded">
                            <h2 class="text-lg font-medium">Approval pending</h2>
                            <p class="mt-2 text-sm text-gray-700">Thanks for registering as an owner. Your account is currently pending admin approval. You will be notified once an admin approves your account. Meanwhile, you can browse public listings.</p>
                        </div>
                    @else
                        <div>
                            <h2 class="text-lg font-medium">Welcome, {{ auth()->user()->name }}!</h2>
                            <p class="mt-2 text-sm text-gray-700">Your owner account is approved — access your owner dashboard below.</p>
                            <div class="mt-4">
                                <a href="{{ url('/admin') }}" class="inline-block px-4 py-2 bg-purple-600 text-white rounded">Go to Owner Dashboard</a>
                            </div>
                        </div>
                    @endif
                @else
                    <div>
                        <h2 class="text-lg font-medium">Welcome, {{ auth()->user()->name }}!</h2>
                        <p class="mt-2 text-sm text-gray-700">This is your user dashboard.</p>
                    </div>
                @endif
            @endauth

            @guest
                <p>Please <a href="{{ route('login') }}">login</a> to view your dashboard.</p>
            @endguest
        </div>
    </div>
</x-layouts.app>
