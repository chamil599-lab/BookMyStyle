<?php

namespace App\Filament\Admin\Pages;

use App\Models\SubscriptionPackage;
use App\Support\ManagementAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class SubscriptionPlans extends Page
{
    protected string $view = 'filament.admin.pages.subscription-plans';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    public static function canAccess(): bool
    {
        return ManagementAccess::owner(Filament::auth()->user()?->fresh());
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);
        $user = Filament::auth()->user()->fresh();

        return [
            'packages' => SubscriptionPackage::active()->ordered()->get()->filter(fn ($package) => $package->isAvailable()),
            'pendingSub' => $user->subscriptions()->pending()->with('package')->first(),
            'activeSub' => $user->activeSubscription()->with('package')->first(),
        ];
    }
}
