<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Salon;
use App\Models\Service;

class SearchBar extends Component
{
    public $query = '';

    public function render()
    {
        $salons = collect();
        $services = collect();

        if (!empty($this->query)) {
            $salons = Salon::where('name', 'like', "%{$this->query}%")
                           ->orWhere('location', 'like', "%{$this->query}%")
                           ->get();

            $services = Service::where('name', 'like', "%{$this->query}%")->get();
        }

        // Explicitly pass all variables including query
        return view('livewire.search-bar', [
            'salons' => $salons,
            'services' => $services,
            'query' => $this->query // Add this line
        ]);
    }
}
