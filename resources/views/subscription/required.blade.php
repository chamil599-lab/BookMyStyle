{{-- resources/views/subscription/required.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Required | BookMyStyle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <div class="flex flex-col items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-4xl">
            {{-- Header --}}
            <div class="mb-10 text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 mb-6 text-white rounded-full bg-gradient-to-r from-purple-500 to-pink-500">
                    <i class="text-3xl fas fa-crown"></i>
                </div>
                <h1 class="mb-3 text-4xl font-bold text-gray-900">Subscription Required</h1>
                <p class="max-w-2xl mx-auto text-lg text-gray-600">
                    To access all salon management features and grow your business, you need to choose a subscription plan.
                </p>
            </div>

            @php
                $hasActiveOrPending = auth()->user()->hasActiveOrPendingSubscription();
                $pendingSub = auth()->user()->subscriptions()->where('status', 'pending')->first();
                $activeSub = auth()->user()->activeSubscription;
            @endphp

            {{-- Session Messages --}}
            @if(session('error'))
            <div class="p-6 mb-8 border border-red-200 bg-red-50 rounded-xl">
                <div class="flex items-center">
                    <i class="mr-3 text-xl text-red-600 fas fa-exclamation-circle"></i>
                    <p class="font-semibold text-red-800">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            {{-- Current Status Alert --}}
            @if($pendingSub)
            <div class="p-6 mb-8 border bg-amber-50 border-amber-200 rounded-xl">
                <div class="flex items-center">
                    <i class="mr-3 text-xl fas fa-clock text-amber-600"></i>
                    <div>
                        <h3 class="font-semibold text-amber-800">Your subscription is pending approval</h3>
                        <p class="mt-1 text-amber-700">You have selected the <strong>{{ $pendingSub->package->name }}</strong> package. Please wait for admin approval.</p>
                    </div>
                </div>
            </div>
            @elseif(auth()->user()->subscriptions()->exists())
            <div class="p-6 mb-8 border bg-amber-50 border-amber-200 rounded-xl">
                <div class="flex items-center">
                    <i class="mr-3 text-xl fas fa-exclamation-triangle text-amber-600"></i>
                    <div>
                        <h3 class="font-semibold text-amber-800">Your subscription has expired</h3>
                        <p class="mt-1 text-amber-700">Renew now to continue using all features.</p>
                    </div>
                </div>
            </div>
            @else
            <div class="p-6 mb-8 border border-blue-200 bg-blue-50 rounded-xl">
                <div class="flex items-center">
                    <i class="mr-3 text-xl text-blue-600 fas fa-rocket"></i>
                    <div>
                        <h3 class="font-semibold text-blue-800">Start your salon journey</h3>
                        <p class="mt-1 text-blue-700">Choose a plan to unlock powerful salon management tools.</p>
                    </div>
                </div>
            </div>
            @endif

            {{-- Packages Grid --}}
            <div class="grid gap-8 mb-10 md:grid-cols-3">
                @foreach($packages as $package)
                <div class="relative bg-white rounded-2xl shadow-lg border @if($package->is_popular) border-purple-500 ring-2 ring-purple-500 @else border-gray-200 @endif overflow-hidden transition-transform hover:scale-[1.02]">
                    @if($package->is_popular)
                    <div class="absolute top-0 right-0">
                        <span class="px-4 py-2 text-xs font-bold text-white rounded-bl-lg shadow bg-gradient-to-r from-purple-600 to-pink-600">
                            MOST POPULAR
                        </span>
                    </div>
                    @endif

                    <div class="p-8">
                        {{-- Package Name --}}
                        <h3 class="mb-2 text-2xl font-bold text-gray-900">{{ $package->name }}</h3>
                        <p class="mb-6 text-gray-600">{{ $package->duration_days }} days billing cycle</p>

                        {{-- Price --}}
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">${{ number_format($package->price, 2) }}</span>
                            <span class="text-gray-600">/month</span>
                        </div>

                        {{-- Features List --}}
                        <ul class="mb-8 space-y-3">
                            <li class="flex items-center">
                                <i class="mr-3 text-green-500 fas fa-check"></i>
                                <span>{{ $package->max_salons }} Salon{{ $package->max_salons > 1 ? 's' : '' }}</span>
                            </li>
                            <li class="flex items-center">
                                <i class="mr-3 text-green-500 fas fa-check"></i>
                                <span>{{ $package->max_staff }} Staff Members</span>
                            </li>
                            <li class="flex items-center">
                                <i class="mr-3 text-green-500 fas fa-check"></i>
                                <span>{{ $package->max_services }} Services</span>
                            </li>
                            @if($package->analytics)
                            <li class="flex items-center">
                                <i class="mr-3 text-green-500 fas fa-check"></i>
                                <span>Analytics Dashboard</span>
                            </li>
                            @endif
                            @if($package->priority_support)
                            <li class="flex items-center">
                                <i class="mr-3 text-green-500 fas fa-check"></i>
                                <span>Priority Support</span>
                            </li>
                            @endif
                        </ul>

                        {{-- Select Button --}}
                        @if($pendingSub && $pendingSub->package_id === $package->id)
                            <span class="block w-full px-6 py-3 font-semibold text-center text-white bg-gray-400 rounded-lg cursor-not-allowed">
                                <i class="mr-2 fas fa-clock"></i> Pending Approval
                            </span>
                        @elseif($pendingSub)
                            <span class="block w-full px-6 py-3 font-semibold text-center text-white bg-gray-400 rounded-lg cursor-not-allowed">
                                <i class="mr-2 fas fa-exchange-alt"></i> Change Plan
                            </span>
                        @elseif($activeSub && $activeSub->package_id === $package->id)
                            <span class="block w-full px-6 py-3 font-semibold text-center text-white rounded-lg cursor-not-allowed bg-gradient-to-r from-emerald-500 to-emerald-600">
                                <i class="mr-2 fas fa-check-circle"></i> Current Plan
                            </span>
                        @elseif($activeSub)
                            <a href="{{ route('subscription.checkout', $package) }}"
                               class="block w-full @if($package->is_popular) bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 @else bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 @endif text-white text-center font-semibold py-3 px-6 rounded-lg transition duration-300">
                                <i class="mr-2 fas fa-exchange-alt"></i> Change Plan
                            </a>
                        @else
                            <a href="{{ route('subscription.checkout', $package) }}"
                               class="block w-full @if($package->is_popular) bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 @else bg-gray-900 hover:bg-black @endif text-white text-center font-semibold py-3 px-6 rounded-lg transition duration-300">
                                @if($package->is_popular)
                                    <i class="mr-2 fas fa-bolt"></i> Get Started
                                @else
                                    Choose Plan
                                @endif
                            </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- FAQ Section --}}
            <div class="p-8 bg-white shadow-lg rounded-2xl">
                <h2 class="mb-6 text-2xl font-bold text-gray-900">Frequently Asked Questions</h2>
                <div class="space-y-4">
                    <div class="pb-4 border-b border-gray-200">
                        <h3 class="mb-2 font-semibold text-gray-900">Can I change plans later?</h3>
                        <p class="text-gray-600">Yes, you can upgrade or downgrade your plan at any time.</p>
                    </div>
                    <div class="pb-4 border-b border-gray-200">
                        <h3 class="mb-2 font-semibold text-gray-900">Is there a free trial?</h3>
                        <p class="text-gray-600">No free trial, but you can cancel within 14 days for a full refund.</p>
                    </div>
                    <div>
                        <h3 class="mb-2 font-semibold text-gray-900">Need help choosing?</h3>
                        <p class="text-gray-600">Contact our sales team at <span class="text-purple-600">support@bookmystyle.com</span></p>
                    </div>
                </div>
            </div>

            {{-- Support Link --}}
            <div class="mt-8 text-center">
                <a href="{{ url('/contact') }}" class="font-medium text-purple-600 hover:text-purple-800">
                    <i class="mr-2 fas fa-question-circle"></i>Need help? Contact Support
                </a>
            </div>
        </div>
    </div>
</body>
</html>