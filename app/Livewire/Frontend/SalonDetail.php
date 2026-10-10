<?php

namespace App\Livewire\Frontend;

use App\Models\Salon;
use Livewire\Component;

class SalonDetail extends Component
{
    public $salon;

    public function mount(Salon $salon)
    {
        $this->salon = $salon;
    }

    public function render()
{
    return view('livewire.frontend.salon-detail', [
        'salon' => $this->salon,
    ]);
}
}
