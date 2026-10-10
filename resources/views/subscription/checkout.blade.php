<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | BookMyStyle</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50" x-data="{ method: '' }" xmlns:x-on="http://www.w3.org/1999/xhtml">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <div class="flex flex-col items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-2xl">

            {{-- Back Link --}}
            <div class="mb-6">
                <a href="{{ route('subscription.required') }}" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                    <i class="mr-2 fas fa-arrow-left"></i> Back to Packages
                </a>
            </div>

            {{-- Error Message --}}
            @if(session('error'))
            <div class="p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                <div class="flex items-center">
                    <i class="mr-3 text-red-600 fas fa-exclamation-circle"></i>
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            @if($errors->any())
            <div class="p-4 mb-6 border border-red-200 bg-red-50 rounded-xl">
                <ul class="text-sm text-red-800 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Package Summary Card --}}
            <div class="mb-8 overflow-hidden bg-white shadow-lg rounded-2xl">
                <div class="p-6 bg-gradient-to-r from-purple-600 to-pink-500">
                    <h1 class="text-2xl font-bold text-white">Checkout</h1>
                    <p class="mt-1 text-purple-100">Complete your subscription request</p>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between pb-6 mb-6 border-b border-gray-200">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900">{{ $package->name }}</h2>
                            <p class="mt-1 text-sm text-gray-600">{{ $package->duration_days }} days billing cycle</p>
                        </div>
                        <div class="text-right">
                            <p class="text-3xl font-bold text-gray-900">LKR {{ number_format($package->price, 2) }}</p>
                        </div>
                    </div>

                    {{-- Features --}}
                    <div class="mb-6">
                        <h3 class="mb-3 text-sm font-semibold tracking-wider text-gray-500 uppercase">Included Features</h3>
                        <ul class="space-y-2">
                            <li class="flex items-center text-sm text-gray-700">
                                <i class="w-5 mr-2 text-green-500 fas fa-check"></i>
                                {{ $package->max_salons }} Salon{{ $package->max_salons > 1 ? 's' : '' }}
                            </li>
                            <li class="flex items-center text-sm text-gray-700">
                                <i class="w-5 mr-2 text-green-500 fas fa-check"></i>
                                {{ $package->max_staff }} Staff Members
                            </li>
                            <li class="flex items-center text-sm text-gray-700">
                                <i class="w-5 mr-2 text-green-500 fas fa-check"></i>
                                {{ $package->max_services }} Services
                            </li>
                            @if($package->analytics)
                            <li class="flex items-center text-sm text-gray-700">
                                <i class="w-5 mr-2 text-green-500 fas fa-check"></i>
                                Analytics Dashboard
                            </li>
                            @endif
                            @if($package->priority_support)
                            <li class="flex items-center text-sm text-gray-700">
                                <i class="w-5 mr-2 text-green-500 fas fa-check"></i>
                                Priority Support
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Payment Form --}}
            <div class="overflow-hidden bg-white shadow-lg rounded-2xl">
                <div class="p-6 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-bold text-gray-900">
                        <i class="mr-2 text-purple-600 fas fa-credit-card"></i> Payment Details
                    </h2>
                </div>

                <form action="{{ route('subscription.subscribe', $package) }}" method="POST" class="p-6 space-y-6">
                    @csrf

                    {{-- Payment Method --}}
                    <div>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" x-model="method" required
                            class="block w-full px-4 py-3 text-sm bg-white border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                            <option value="">-- Select Payment Method --</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="one_pay">One Pay</option>
                        </select>
                    </div>

                    {{-- Transaction ID (for bank transfer) --}}
                    <div x-show="method === 'bank_transfer'" x-transition>
                        <label class="block mb-2 text-sm font-medium text-gray-700">Transaction ID <span class="text-red-500">*</span></label>
                        <input type="text" name="transaction_id" placeholder="e.g. TXN-20260130-001"
                            class="block w-full px-4 py-3 font-mono text-sm bg-white border-2 border-gray-300 rounded-xl focus:border-purple-500 focus:ring-purple-500">
                        <p class="mt-2 text-xs text-gray-500">
                            <i class="mr-1 fas fa-info-circle"></i>
                            Enter the transaction ID from your bank transfer receipt.
                        </p>
                    </div>

                    {{-- One Pay info --}}
                    <div x-show="method === 'one_pay'" x-transition>
                        <div class="flex items-center gap-3 p-4 border border-blue-200 bg-blue-50 rounded-xl">
                            <i class="text-blue-600 fas fa-info-circle"></i>
                            <p class="text-sm text-blue-800">One Pay integration will be available soon. Please use Bank Transfer for now.</p>
                        </div>
                    </div>

                    {{-- Info Notice --}}
                    <div class="flex items-start gap-3 p-4 border border-amber-200 bg-amber-50 rounded-xl">
                        <i class="mt-0.5 text-amber-600 fas fa-clock"></i>
                        <div>
                            <p class="text-sm font-medium text-amber-800">Pending Admin Approval</p>
                            <p class="mt-1 text-sm text-amber-700">Your subscription will be activated after the admin verifies your payment.</p>
                        </div>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="block w-full py-3 px-6 text-center font-semibold text-white bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 rounded-lg transition duration-300 shadow-lg hover:shadow-xl">
                        <i class="mr-2 fas fa-paper-plane"></i> Submit Subscription Request
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
