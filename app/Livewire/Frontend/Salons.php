<?php

namespace App\Livewire\Frontend;

use App\Models\Salon;
use Livewire\Component;
use Livewire\WithPagination;

class Salons extends Component
{
    use WithPagination;

    public $search = '';

    public function render()
    {
        $salons = Salon::where('name', 'like', "%{$this->search}%")
            ->paginate(9);

        return view('livewire.frontend.salons', compact('salons'));

    }
}
