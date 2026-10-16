<?php

namespace App\Filament\Admin\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function mount(): void
    {
        parent::mount();

        // Show notification if redirected due to pending approval
        if (session('notification')) {
            $notification = session('notification');

            Notification::make()
                ->title($notification['message'])
                ->status($notification['status'] ?? 'warning')
                ->persistent()
                ->send();
        }
    }
}
