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
        $this->salonId = Salon::findOrFail($salonId)->id;
    }

    public function submit(): void
    {
        abort_if(! RateLimiter::attempt('livewire-booking:'.request()->ip(), 10, fn () => true, 60), 429);
        app(BookingService::class)->create([
            'salon_id' => $this->salonId, 'service_id' => $this->service_id,
            'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone,
            'date' => $this->date, 'time' => $this->time,
        ], auth()->user());
        session()->flash('message', 'Booking successfully submitted!');
        $this->reset(['name', 'email', 'phone', 'service_id', 'date', 'time']);
    }

    public function render()
    {
        $salon = Salon::findOrFail($this->salonId);

        return view('livewire.booking-form', ['salon' => $salon,
            'services' => Service::where('created_by', $salon->created_by)->get()]);
    }
}
