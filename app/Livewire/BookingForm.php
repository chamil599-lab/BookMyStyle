<?php

namespace App\Livewire;

use App\Models\Salon;
use App\Models\Service;
use App\Services\BookingService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BookingForm extends Component
{
    #[Locked]
    public int $salonId;

    public $name;

    public $email;

    public $phone;

    public $service_id;

    public $date;

    public $time;

    public function mount(int $salonId): void
    {
        $this->salonId = Salon::publiclyVisible()->findOrFail($salonId)->id;
        $this->name = auth()->user()?->name;
        $this->email = auth()->user()?->email;
        $this->phone = auth()->user()?->phone;
    }

    public function submit(): void
    {
        abort_if(! RateLimiter::attempt('livewire-booking:'.request()->ip(), 10, fn () => true, 60), 429);
        abort_unless(Salon::publiclyVisible()->whereKey($this->salonId)->exists(), 404);
        app(BookingService::class)->create([
            'salon_id' => $this->salonId, 'service_id' => $this->service_id,
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone,
            'date' => $this->date, 'time' => $this->time,
        ], auth()->user());
        session()->flash('message', 'Your booking request has been sent. The salon will confirm your appointment.');
        $this->reset(['name', 'email', 'phone', 'service_id', 'date', 'time']);
    }

    public function render()
    {
        $salon = Salon::publiclyVisible()->with('owner')->findOrFail($this->salonId);

        $slots = [];
        if (is_string($this->date) && \Illuminate\Support\Facades\Validator::make(['date' => $this->date], ['date' => 'required|date_format:Y-m-d'])->passes()
            && $this->date >= today()->format('Y-m-d') && is_scalar($this->service_id) && ctype_digit((string) $this->service_id)) {
            $service = Service::published()->where('created_by', $salon->created_by)->find((int) $this->service_id);
            if ($service) {
                $slots = app(BookingService::class)->availableSlots($salon, $this->date, $service);
            }
        }

        return view('livewire.booking-form', ['salon' => $salon,
            'slots' => $slots, 'canBook' => $salon->owner->hasActiveSubscription(),
            'services' => Service::published()->where('created_by', $salon->created_by)->get()]);
    }
}
