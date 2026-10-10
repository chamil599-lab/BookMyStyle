<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Service;
use App\Models\Salon;
use App\Models\Booking;

class BookingForm extends Component
{
    public $salonId;
    public $name;
    public $email;
    public $phone;
    public $service_id;
    public $date;
    public $time;
    public $requests;

    public $services = [];
    public $timeSlots = [];
    public $salon;
    public $bookedSlots = []; // Store already booked time slots

    public function mount($salonId)
    {
        $this->salonId = $salonId;
        $this->salon = Salon::findOrFail($salonId);
        $this->services = Service::where('created_by', $salonId)->get();
        $this->generateTimeSlots();
    }

    public function generateTimeSlots()
    {
        $this->timeSlots = [];
        $openTime = $this->salon->open_time;
        $closeTime = $this->salon->close_time;
        $start = strtotime($openTime);
        $end = strtotime($closeTime);

        while ($start < $end) {
            $this->timeSlots[] = date('H:i', $start);
            $start = strtotime('+30 minutes', $start);
        }
    }

    // This method will be called when date changes
    public function updatedDate($value)
    {
        if ($value) {
            $this->getBookedSlots();
        } else {
            $this->bookedSlots = [];
        }
        $this->time = null; // Reset time when date changes
    }

    // Get already booked time slots for the selected date
    public function getBookedSlots()
    {
        if (!$this->date) {
            $this->bookedSlots = [];
            return;
        }

        $this->bookedSlots = Booking::where('salon_id', $this->salonId)
            ->where('date', $this->date)
            ->pluck('time')
            ->map(function ($time) {
                return date('H:i', strtotime($time)); // Ensure consistent format
            })
            ->toArray();
    }

    // Check if a time slot is available
    public function isSlotAvailable($slot)
    {
        return !in_array($slot, $this->bookedSlots);
    }

    public function submit()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'required|string',
            'service_id' => 'required|exists:services,id',
            'date' => 'required|date',
            'time' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$this->isSlotAvailable($value)) {
                        $fail('This time slot is already booked. Please choose another time.');
                    }
                },
            ],
        ]);

        // Save booking to database
        Booking::create([
            'salon_id' => $this->salonId,
            'service_id' => $this->service_id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'date' => $this->date,
            'time' => $this->time,
            
        ]);

        session()->flash('message', 'Booking successfully submitted!');
        $this->reset(['name', 'email', 'phone', 'service_id', 'date', 'time', 'requests']);
        $this->bookedSlots = []; // Reset booked slots
    }

    public function render()
    {
        return view('livewire.booking-form');
    }
}