@if(app(\App\Services\AccountNotifications::class)->canAccess(auth()->user()?->fresh()))
    @livewire('notification-bell')
@endif
