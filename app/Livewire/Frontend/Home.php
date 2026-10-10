<?php

namespace App\Livewire\Frontend;

use App\Models\Salon;
use Livewire\Component;

class Home extends Component
{
    public function render()
    {
        $salons = Salon::all();

        return view('livewire.frontend.home', compact('salons'));
    }
}
