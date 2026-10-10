<x-filament-panels::page>
    <div class="text-center py-12">
        <div class="mb-6">
            <div class="mx-auto w-24 h-24 rounded-full bg-warning-100 dark:bg-warning-900/20 flex items-center justify-center">
                <x-filament::icon 
                    icon="heroicon-o-clock"
                    class="w-12 h-12 text-warning-600 dark:text-warning-400"
                />
            </div>
        </div>
        
        <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
            Your Account is Under Review
        </h2>
        
        <p class="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto mb-8">
            Thank you for registering as a salon owner! Your account is currently pending approval from our admin team. 
            You will receive an email notification once your account has been approved and you can start managing your salon.
        </p>
        
        <x-filament::section class="max-w-2xl mx-auto">
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-user-circle" class="w-5 h-5" />
                    Account Details
                </div>
            </x-slot>
            
            <div class="space-y-3 text-left">
                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Name:</span>
                    <span class="text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Email:</span>
                    <span class="text-gray-900 dark:text-white">{{ auth()->user()->email }}</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-gray-700">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Registered:</span>
                    <span class="text-gray-900 dark:text-white">{{ auth()->user()->created_at->format('M d, Y h:i A') }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="font-semibold text-gray-700 dark:text-gray-300">Status:</span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold bg-warning-100 text-warning-700 dark:bg-warning-900/20 dark:text-warning-400">
                        <x-filament::icon icon="heroicon-o-clock" class="w-4 h-4" />
                        Pending Approval
                    </span>
                </div>
            </div>
        </x-filament::section>
        
        <x-filament::section class="mt-6 max-w-2xl mx-auto bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800">
            <div class="text-left">
                <div class="flex items-start gap-3 mb-4">
                    <x-filament::icon icon="heroicon-o-information-circle" class="w-6 h-6 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-2">What happens next?</h3>
                        <ul class="space-y-2 text-sm text-blue-800 dark:text-blue-200">
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">1.</span>
                                <span>Our admin team will review your salon owner registration</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">2.</span>
                                <span>You'll receive an email confirmation once your account is approved</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <span class="text-blue-600 dark:text-blue-400 font-bold">3.</span>
                                <span>After approval, you can access your salon dashboard and start managing bookings</span>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-blue-900 dark:text-blue-100">
                        <strong>Need help?</strong> Contact our support team at 
                        <a href="mailto:info@bookmystyle.com" class="underline hover:no-underline">info@bookmystyle.com</a>
                    </p>
                </div>
            </div>
        </x-filament::section>
        
        <div class="mt-10">
            <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
                @csrf
                <x-filament::button 
                    type="submit" 
                    color="gray" 
                    size="lg"
                    icon="heroicon-o-arrow-left-on-rectangle"
                >
                    Logout
                </x-filament::button>
            </form>
        </div>
    </div>
</x-filament-panels::page>