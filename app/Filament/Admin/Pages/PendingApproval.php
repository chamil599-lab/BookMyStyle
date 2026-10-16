<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class PendingApproval extends Page
{
    protected string $view = 'filament.admin.pages.pending-approval';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $user = auth()->user();

        if ($user && ! $user->hasRole('owner')) {
            abort(403);
        }
    }
}
