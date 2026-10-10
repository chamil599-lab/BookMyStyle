<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Models\UserSubscription;
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
        if (!$this->editPaymentMethod) {
            Notification::make()
                ->title('Payment method is required.')
                ->danger()
                ->send();
            return;
        }

        if ($this->editPaymentMethod === 'bank_transfer' && empty($this->editTransactionId)) {
            Notification::make()
                ->title('Transaction ID is required for bank transfer.')
                ->danger()
                ->send();
            return;
        }

        $this->record->update([
            'payment_method' => $this->editPaymentMethod,
            'transaction_id' => $this->editPaymentMethod === 'bank_transfer'
                ? $this->editTransactionId
                : null,
        ]);

        Notification::make()
            ->title('Payment details updated successfully.')
            ->success()
            ->send();
    }

    public function renewSubscription(): void
    {
        // Block duplicate pending renewals
        $hasPendingRenewal = UserSubscription::where('user_id', $this->record->user_id)
            ->where('renewed_from', $this->record->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingRenewal) {
            Notification::make()
                ->title('You already have a pending renewal request.')
                ->danger()
                ->send();
            return;
        }

        if (!$this->renewPaymentMethod) {
            Notification::make()
                ->title('Payment method is required.')
                ->danger()
                ->send();
            return;
        }

        if ($this->renewPaymentMethod === 'bank_transfer' && empty($this->renewTransactionId)) {
            Notification::make()
                ->title('Transaction ID is required for bank transfer.')
                ->danger()
                ->send();
            return;
        }

        $newSubscription = UserSubscription::create([
            'user_id' => $this->record->user_id,
            'package_id' => $this->record->package_id,
            'amount_paid' => 0,
            'payment_method' => $this->renewPaymentMethod,
            'transaction_id' => $this->renewPaymentMethod === 'bank_transfer'
                ? $this->renewTransactionId
                : null,
            'status' => 'pending',
            'renewed_from' => $this->record->id,
        ]);

        Notification::make()
            ->title('Renewal request submitted!')
            ->body('Your renewal is pending admin approval.')
            ->success()
            ->send();

        $this->redirect(
            UserSubscriptionResource::getUrl('view', ['record' => $newSubscription->id])
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }
}
