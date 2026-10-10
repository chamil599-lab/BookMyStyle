<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewUserSubscription extends ViewRecord
{
    protected static string $resource = UserSubscriptionResource::class;

    protected string $view = 'filament.resources.user-subscriptions.pages.view-subscription-details';

    public ?string $editPaymentMethod = null;

    public ?string $editTransactionId = null;

    public ?string $renewPaymentMethod = null;

    public ?string $renewTransactionId = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load(['package', 'user']);

        $this->editPaymentMethod = $this->record->payment_method;
        $this->editTransactionId = $this->record->transaction_id;
    }

    public function selectPaymentMethod(string $method): void
    {
        $this->editPaymentMethod = $method;

        if ($method !== 'bank_transfer') {
            $this->editTransactionId = null;
        }
    }

    public function savePaymentDetails(): void
    {
        $this->record = app(\App\Services\SubscriptionService::class)->updatePayment($this->record, auth()->user(), [
            'payment_method' => $this->editPaymentMethod, 'transaction_id' => $this->editTransactionId]);
        Notification::make()->title('Payment details updated successfully.')->success()->send();
    }

    public function renewSubscription(): void
    {
        $newSubscription = app(\App\Services\SubscriptionService::class)->request($this->record->user, $this->record->package, [
            'payment_method' => $this->renewPaymentMethod, 'transaction_id' => $this->renewTransactionId], auth()->user());
        Notification::make()->title('Renewal request submitted!')->body('Your renewal is pending admin approval.')->success()->send();
        $this->redirect(UserSubscriptionResource::getUrl('view', ['record' => $newSubscription->id]));
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }
}
