<?php

namespace App\Livewire;

use App\Services\AccountNotifications;
use Livewire\Component;

class NotificationBell extends Component
{
    protected $listeners = ['notifications-updated' => '$refresh'];

    public function render()
    {
        $notifications = app(AccountNotifications::class);
        $user = $notifications->actor(auth()->user());

        return view('livewire.notification-bell', ['unread' => $notifications->unread($user),
            'inboxUrl' => $notifications->inboxUrl($user)]);
    }
}
