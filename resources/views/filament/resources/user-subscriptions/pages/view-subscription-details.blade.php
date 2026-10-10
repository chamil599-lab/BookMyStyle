<x-filament-panels::page>
    @php
        $record = $this->record;
        $package = $record->package;
        $user = $record->user;
        $isAdmin = auth()->user()->hasRole('admin');
        $isPending = $record->isPending();
        $isActive = $record->isActive();
        $isExpired = $record->isExpired();
        $isCanceled = $record->status === 'canceled';
        $hasPendingRenewal = \App\Models\UserSubscription::where('renewed_from', $record->id)
            ->where('status', 'pending')->exists();
    @endphp

    <div class="space-y-6">

        {{-- Status Banner --}}
        <div @class([
            'rounded-xl p-4 sm:p-6 border',
            'bg-warning-50 border-warning-200 dark:bg-warning-950/20 dark:border-warning-800' => $isPending,
            'bg-success-50 border-success-200 dark:bg-success-950/20 dark:border-success-800' => $isActive,
            'bg-danger-50 border-danger-200 dark:bg-danger-950/20 dark:border-danger-800' => $isExpired,
            'bg-gray-50 border-gray-200 dark:bg-gray-950/20 dark:border-gray-800' => $isCanceled,
        ])>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div @class([
                        'w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0',
                        'bg-warning-100 dark:bg-warning-900/30' => $isPending,
                        'bg-success-100 dark:bg-success-900/30' => $isActive,
                        'bg-danger-100 dark:bg-danger-900/30' => $isExpired,
                        'bg-gray-100 dark:bg-gray-900/30' => $isCanceled,
                    ])>
                        @if($isPending)
                            <x-filament::icon icon="heroicon-o-clock" class="w-6 h-6 text-warning-600 dark:text-warning-400" />
                        @elseif($isActive)
                            <x-filament::icon icon="heroicon-o-check-circle" class="w-6 h-6 text-success-600 dark:text-success-400" />
                        @elseif($isExpired)
                            <x-filament::icon icon="heroicon-o-x-circle" class="w-6 h-6 text-danger-600 dark:text-danger-400" />
                        @else
                            <x-filament::icon icon="heroicon-o-no-symbol" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                        @endif
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $package?->name ?? 'Unknown Package' }}
                        </h2>
                        <div class="flex items-center gap-2 mt-1">
                            <span @class([
                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold',
                                'bg-warning-100 text-warning-800 dark:bg-warning-900/30 dark:text-warning-400' => $isPending,
                                'bg-success-100 text-success-800 dark:bg-success-900/30 dark:text-success-400' => $isActive,
                                'bg-danger-100 text-danger-800 dark:bg-danger-900/30 dark:text-danger-400' => $isExpired,
                                'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400' => $isCanceled,
                            ])>
                                {{ ucfirst($record->status) }}
                            </span>
                            @if($isActive && $record->ends_at)
                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $record->daysRemaining() }} days remaining
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        LKR {{ number_format($record->amount_paid, 2) }}
                    </p>
                    @if($package)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Package price: LKR {{ number_format($package->price, 2) }}
                        </p>
                    @endif
                </div>
            </div>

            {{-- Progress Bar for Active Subscriptions --}}
            @if($isActive && $record->starts_at && $record->ends_at)
                @php
                    $totalDays = (int) $record->starts_at->diffInDays($record->ends_at);
                    $elapsedDays = (int) $record->starts_at->diffInDays(now());
                    $progress = $totalDays > 0 ? min(100, round(($elapsedDays / $totalDays) * 100)) : 0;
                @endphp
                <div class="mt-4">
                    <div class="flex justify-between mb-1 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $record->starts_at->format('M d, Y') }}</span>
                        <span>{{ $record->ends_at->format('M d, Y') }}</span>
                    </div>
                    <div class="w-full h-2 bg-gray-200 rounded-full dark:bg-gray-700">
                        <div
                            class="h-2 transition-all rounded-full bg-success-500 dark:bg-success-400"
                            style="width: {{ $progress }}%"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-center text-gray-500 dark:text-gray-400">
                        {{ $elapsedDays }} of {{ $totalDays }} days used ({{ $progress }}%)
                    </p>
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Subscription Details --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-document-text" class="w-5 h-5" />
                        Subscription Details
                    </div>
                </x-slot>

                <div class="space-y-3">
                    @if($isAdmin)
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Subscriber</span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user?->name ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $user?->email ?? '-' }}</span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Package</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package?->name ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Duration</span>
                        <span class="text-sm text-gray-900 dark:text-white">{{ $package?->duration_days ?? '-' }} days</span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Starts At</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $record->starts_at ? $record->starts_at->format('M d, Y h:i A') : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Ends At</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $record->ends_at ? $record->ends_at->format('M d, Y h:i A') : '-' }}
                        </span>
                    </div>
                    @if($record->cancelled_at)
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Cancelled At</span>
                            <span class="text-sm text-danger-600 dark:text-danger-400">
                                {{ $record->cancelled_at->format('M d, Y h:i A') }}
                            </span>
                        </div>
                    @endif
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</span>
                        <span class="text-sm text-gray-900 dark:text-white">
                            {{ $record->created_at->format('M d, Y h:i A') }}
                        </span>
                    </div>
                </div>
            </x-filament::section>

            {{-- Payment Details --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-credit-card" class="w-5 h-5" />
                        Payment Details
                    </div>
                </x-slot>

                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Amount Paid</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            LKR {{ number_format($record->amount_paid, 2) }}
                        </span>
                    </div>

                    @if($isPending)
                        {{-- Interactive payment form using Alpine.js --}}
                        <div
                            x-data="{
                                method: '{{ $record->payment_method ?? '' }}',
                                transactionId: '{{ $record->transaction_id ?? '' }}',
                                submitting: false,
                                async submit() {
                                    this.submitting = true;
                                    await $wire.set('editPaymentMethod', this.method);
                                    await $wire.set('editTransactionId', this.method === 'bank_transfer' ? this.transactionId : null);
                                    await $wire.savePaymentDetails();
                                    this.submitting = false;
                                }
                            }"
                            class="space-y-3"
                        >
                            {{-- Payment Method Selection --}}
                            <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</label>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Bank Transfer --}}
                                    <button
                                        type="button"
                                        x-on:click="method = 'bank_transfer'"
                                        :class="method === 'bank_transfer'
                                            ? 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-primary-500 bg-primary-50 dark:bg-primary-950/20 ring-1 ring-primary-500'
                                            : 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                    >
                                        <div
                                            :class="method === 'bank_transfer'
                                                ? 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-primary-100 dark:bg-primary-900/30'
                                                : 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-100 dark:bg-gray-800'"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-o-building-library"
                                                x-bind:class="method === 'bank_transfer'
                                                    ? 'w-4 h-4 text-primary-600 dark:text-primary-400'
                                                    : 'w-4 h-4 text-gray-500 dark:text-gray-400'"
                                            />
                                        </div>
                                        <span
                                            :class="method === 'bank_transfer'
                                                ? 'text-sm font-medium text-primary-700 dark:text-primary-300'
                                                : 'text-sm font-medium text-gray-700 dark:text-gray-300'"
                                        >Bank Transfer</span>
                                        <template x-if="method === 'bank_transfer'">
                                            <x-filament::icon icon="heroicon-s-check-circle" class="absolute w-5 h-5 text-primary-500 top-2 right-2" />
                                        </template>
                                    </button>

                                    {{-- OnePay --}}
                                    <button
                                        type="button"
                                        x-on:click="method = 'one_pay'"
                                        :class="method === 'one_pay'
                                            ? 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-primary-500 bg-primary-50 dark:bg-primary-950/20 ring-1 ring-primary-500'
                                            : 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                    >
                                        <div
                                            :class="method === 'one_pay'
                                                ? 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-primary-100 dark:bg-primary-900/30'
                                                : 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-100 dark:bg-gray-800'"
                                        >
                                            <x-filament::icon
                                                icon="heroicon-o-device-phone-mobile"
                                                x-bind:class="method === 'one_pay'
                                                    ? 'w-4 h-4 text-primary-600 dark:text-primary-400'
                                                    : 'w-4 h-4 text-gray-500 dark:text-gray-400'"
                                            />
                                        </div>
                                        <span
                                            :class="method === 'one_pay'
                                                ? 'text-sm font-medium text-primary-700 dark:text-primary-300'
                                                : 'text-sm font-medium text-gray-700 dark:text-gray-300'"
                                        >OnePay</span>
                                        <template x-if="method === 'one_pay'">
                                            <x-filament::icon icon="heroicon-s-check-circle" class="absolute w-5 h-5 text-primary-500 top-2 right-2" />
                                        </template>
                                    </button>
                                </div>
                            </div>

                            {{-- Transaction ID (for bank transfer) --}}
                            <div x-show="method === 'bank_transfer'" x-cloak class="py-2 border-b border-gray-200 dark:border-gray-700">
                                <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</label>
                                <input
                                    type="text"
                                    x-model="transactionId"
                                    placeholder="Enter your bank transfer transaction ID"
                                    class="w-full text-sm border-gray-300 rounded-lg shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                />
                            </div>

                            {{-- Submit Button --}}
                            <div x-show="method" x-cloak class="pt-2">
                                <button
                                    type="button"
                                    x-on:click="submit()"
                                    :disabled="submitting"
                                    class="w-full inline-flex items-center justify-center gap-1 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-gray-500 shadow-sm hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 transition-all"
                                >
                                    <span x-show="!submitting">Submit Payment Details</span>
                                    <span x-show="submitting" x-cloak>Saving...</span>
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- Read-only payment display --}}
                        <div class="py-2 border-b border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</span>
                                <span class="text-sm text-gray-900 dark:text-white">
                                    @if($record->payment_method === 'bank_transfer')
                                        Bank Transfer
                                    @elseif($record->payment_method === 'one_pay')
                                        OnePay
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-gray-200 dark:border-gray-700">
                            <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $record->transaction_id ?? '-' }}</span>
                        </div>

                        {{-- Renew Section: show when ≤3 days remaining or expired, and no pending renewal --}}
                        @if((($isActive && $record->daysRemaining() <= 3) || $isExpired) && !$hasPendingRenewal)
                            <div class="pt-3">
                                <div class="p-3 mb-3 border rounded-lg border-warning-300 dark:border-warning-700 bg-warning-50 dark:bg-warning-950/20">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                                        <p class="text-sm font-medium text-warning-700 dark:text-warning-400">
                                            @if($isExpired)
                                                Your subscription has expired. Renew now to continue.
                                            @else
                                                Your subscription expires in {{ $record->daysRemaining() }} day(s). Renew now!
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div
                                    x-data="{
                                        method: '',
                                        transactionId: '',
                                        submitting: false,
                                        async submit() {
                                            this.submitting = true;
                                            await $wire.set('renewPaymentMethod', this.method);
                                            await $wire.set('renewTransactionId', this.method === 'bank_transfer' ? this.transactionId : null);
                                            await $wire.renewSubscription();
                                            this.submitting = false;
                                        }
                                    }"
                                    class="space-y-3"
                                >
                                    {{-- Payment Method Selection --}}
                                    <div>
                                        <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">Payment Method</label>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button
                                                type="button"
                                                x-on:click="method = 'bank_transfer'"
                                                :class="method === 'bank_transfer'
                                                    ? 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-primary-500 bg-primary-50 dark:bg-primary-950/20 ring-1 ring-primary-500'
                                                    : 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                            >
                                                <div :class="method === 'bank_transfer'
                                                    ? 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-primary-100 dark:bg-primary-900/30'
                                                    : 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-100 dark:bg-gray-800'">
                                                    <x-filament::icon icon="heroicon-o-building-library"
                                                        x-bind:class="method === 'bank_transfer'
                                                            ? 'w-4 h-4 text-primary-600 dark:text-primary-400'
                                                            : 'w-4 h-4 text-gray-500 dark:text-gray-400'" />
                                                </div>
                                                <span :class="method === 'bank_transfer'
                                                    ? 'text-sm font-medium text-primary-700 dark:text-primary-300'
                                                    : 'text-sm font-medium text-gray-700 dark:text-gray-300'">Bank Transfer</span>
                                                <template x-if="method === 'bank_transfer'">
                                                    <x-filament::icon icon="heroicon-s-check-circle" class="absolute w-5 h-5 text-primary-500 top-2 right-2" />
                                                </template>
                                            </button>

                                            <button
                                                type="button"
                                                x-on:click="method = 'one_pay'"
                                                :class="method === 'one_pay'
                                                    ? 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-primary-500 bg-primary-50 dark:bg-primary-950/20 ring-1 ring-primary-500'
                                                    : 'relative flex items-center gap-3 rounded-lg border-2 p-3 cursor-pointer transition-all border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
                                            >
                                                <div :class="method === 'one_pay'
                                                    ? 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-primary-100 dark:bg-primary-900/30'
                                                    : 'w-8 h-8 rounded-full flex items-center justify-center shrink-0 bg-gray-100 dark:bg-gray-800'">
                                                    <x-filament::icon icon="heroicon-o-device-phone-mobile"
                                                        x-bind:class="method === 'one_pay'
                                                            ? 'w-4 h-4 text-primary-600 dark:text-primary-400'
                                                            : 'w-4 h-4 text-gray-500 dark:text-gray-400'" />
                                                </div>
                                                <span :class="method === 'one_pay'
                                                    ? 'text-sm font-medium text-primary-700 dark:text-primary-300'
                                                    : 'text-sm font-medium text-gray-700 dark:text-gray-300'">OnePay</span>
                                                <template x-if="method === 'one_pay'">
                                                    <x-filament::icon icon="heroicon-s-check-circle" class="absolute w-5 h-5 text-primary-500 top-2 right-2" />
                                                </template>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Transaction ID (bank transfer only) --}}
                                    <div x-show="method === 'bank_transfer'" x-cloak>
                                        <label class="block mb-2 text-sm font-medium text-gray-500 dark:text-gray-400">Transaction ID</label>
                                        <input
                                            type="text"
                                            x-model="transactionId"
                                            placeholder="Enter your bank transfer transaction ID"
                                            class="w-full text-sm border-gray-300 rounded-lg shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                                        />
                                    </div>

                                    {{-- Renew Button --}}
                                    <div x-show="method" x-cloak class="pt-1">
                                        <button
                                            type="button"
                                            x-on:click="submit()"
                                            :disabled="submitting"
                                            class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-warning-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-warning-500 focus:outline-none focus:ring-2 focus:ring-warning-500 focus:ring-offset-2 disabled:opacity-50 transition-all"
                                        >
                                            <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4" />
                                            <span x-show="!submitting">Renew Subscription</span>
                                            <span x-show="submitting" x-cloak>Submitting...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($hasPendingRenewal && (($isActive && $record->daysRemaining() <= 3) || $isExpired))
                            <div class="pt-3">
                                <div class="p-3 border rounded-lg border-info-300 dark:border-info-700 bg-info-50 dark:bg-info-950/20">
                                    <div class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-info-600 dark:text-info-400" />
                                        <p class="text-sm font-medium text-info-700 dark:text-info-400">
                                            Renewal request submitted. Waiting for admin approval.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </x-filament::section>
        </div>

        {{-- Features & Limits --}}
        @if($package)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-sparkles" class="w-5 h-5" />
                        Features & Limits
                    </div>
                </x-slot>

                <div class="space-y-3">
                    {{-- Subscription Plan --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-cube" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Subscription</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->name }}</span>
                    </div>

                    {{-- Duration --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Duration</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->duration_days }} days</span>
                    </div>

                    {{-- Max Salons --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-building-storefront" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Max Salons</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->max_salons }}</span>
                    </div>

                    {{-- Max Staff --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-users" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Max Staff</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->max_staff }}</span>
                    </div>

                    {{-- Max Services --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-scissors" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Max Services</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->max_services }}</span>
                    </div>

                    {{-- Max Bookings/Month --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-calendar-days" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Max Bookings</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $package->max_bookings_per_month }} / month</span>
                    </div>

                    {{-- Analytics --}}
                    <div class="flex items-center justify-between py-2.5 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Analytics</span>
                        </div>
                        @if($package->analytics && $isActive)
                            <a
                                href="{{ \App\Filament\Admin\Pages\AnalyticsDashboard::getUrl() }}"
                                class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                            >
                                <x-filament::icon icon="heroicon-o-chart-bar" class="w-4 h-4" />
                                View Analytics
                                <x-filament::icon icon="heroicon-o-arrow-right" class="w-3.5 h-3.5" />
                            </a>
                        @elseif($package->analytics)
                            <span class="inline-flex items-center gap-1 text-sm font-semibold text-success-600 dark:text-success-400">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4" />
                                Included
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-sm text-gray-400 dark:text-gray-500">
                                <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4" />
                                Not included
                            </span>
                        @endif
                    </div>

                    {{-- Priority Support --}}
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-filament::icon icon="heroicon-o-lifebuoy" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Priority Support</span>
                        </div>
                        @if($package->priority_support)
                            <span class="inline-flex items-center gap-1 text-sm font-semibold text-success-600 dark:text-success-400">
                                <x-filament::icon icon="heroicon-o-check-circle" class="w-4 h-4" />
                                Included
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-sm text-gray-400 dark:text-gray-500">
                                <x-filament::icon icon="heroicon-o-x-circle" class="w-4 h-4" />
                                Not included
                            </span>
                        @endif
                    </div>
                </div>
            </x-filament::section>
        @endif

    </div>
</x-filament-panels::page>
