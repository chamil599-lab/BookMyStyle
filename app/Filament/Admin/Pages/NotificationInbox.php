<?php

namespace App\Filament\Admin\Pages;

use App\Services\AccountNotifications;
use App\Support\ManagementAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\WithPagination;

class NotificationInbox extends Page
{
    use WithPagination;

    protected string $view = 'filament.admin.pages.notification-inbox';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?string $title = 'Notifications';

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user()?->fresh();

        return ManagementAccess::admin($user) || ManagementAccess::owner($user);
    }

    public function readNotification(string $id): void
    {
        abort_unless(static::canAccess(), 403);
        app(AccountNotifications::class)->read(auth()->user(), $id);
        $this->dispatch('notifications-updated');
    }

    public function readAll(): void
    {
        abort_unless(static::canAccess(), 403);
        app(AccountNotifications::class)->read(auth()->user());
        $this->dispatch('notifications-updated');
    }

    public function deleteNotification(string $id): void
    {
        abort_unless(static::canAccess(), 403);
        app(AccountNotifications::class)->delete(auth()->user(), $id);
        $this->dispatch('notifications-updated');
    }

    public function clearAll(): void
    {
        abort_unless(static::canAccess(), 403);
        app(AccountNotifications::class)->delete(auth()->user());
        $this->dispatch('notifications-updated');
        $this->resetPage();
    }

    public function getNotificationsProperty()
    {
        abort_unless(static::canAccess(), 403);

        return app(AccountNotifications::class)->query(auth()->user())->paginate(15);
    }
}
