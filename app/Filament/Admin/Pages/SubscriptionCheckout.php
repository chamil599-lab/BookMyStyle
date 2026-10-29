<?php

namespace App\Filament\Admin\Pages;

use App\Models\SubscriptionPackage;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

class SubscriptionCheckout extends Page
{
    protected string $view = 'filament.admin.pages.subscription-checkout';

    protected static ?string $slug = 'subscription-checkout/{package}';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Subscription request';

    #[Locked]
    public int $packageId;

    public static function canAccess(): bool
    {
        return SubscriptionPlans::canAccess();
    }

    public function mount(SubscriptionPackage $package): void
    {
        abort_unless(static::canAccess(), 403);
        abort_unless($package->isAvailable(), 404);
        $this->packageId = $package->id;

        if (Filament::auth()->user()->subscriptions()->pending()->exists()) {
            session()->flash('error', 'You have a pending subscription. Please wait for admin approval before changing plans.');
            $this->redirect(SubscriptionPlans::getUrl(panel: 'admin'));
        }
    }

    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);
        $package = SubscriptionPackage::findOrFail($this->packageId);
        abort_unless($package->isAvailable(), 404);
        $activeSubscription = Filament::auth()->user()->fresh()->activeSubscription;

        return [
            'package' => $package,
            'isChangingPlan' => $activeSubscription && (int) $activeSubscription->package_id !== (int) $package->id,
        ];
    }
}
