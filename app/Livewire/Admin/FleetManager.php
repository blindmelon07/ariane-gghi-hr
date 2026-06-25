<?php

namespace App\Livewire\Admin;

use App\Models\Vehicle;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class FleetManager extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = 'all';
    public string $filterType   = 'all';

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:20')]
    public string $plate_number = '';

    #[Validate('required|string|max:60')]
    public string $make = '';

    #[Validate('required|string|max:60')]
    public string $model = '';

    #[Validate('required|in:sedan,van,SUV,pickup,ambulance,truck')]
    public string $vehicle_type = 'van';

    #[Validate('required|integer|min:1|max:60')]
    public int $capacity = 4;

    #[Validate('nullable|integer|min:1900|max:2100')]
    public string $year = '';

    #[Validate('required|in:available,in_use,under_maintenance')]
    public string $status = 'available';

    public bool $is_active = true;

    public function updatedSearch(): void    { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterType(): void   { $this->resetPage(); }

    #[Computed]
    public function vehicles()
    {
        return Vehicle::query()
            ->when($this->search, fn ($q) => $q
                ->where('plate_number', 'like', "%{$this->search}%")
                ->orWhere('make', 'like', "%{$this->search}%")
                ->orWhere('model', 'like', "%{$this->search}%"))
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterType !== 'all', fn ($q) => $q->where('vehicle_type', $this->filterType))
            ->orderBy('make')
            ->paginate(15);
    }

    public function openCreate(): void
    {
        $this->reset(['plate_number', 'make', 'model', 'year', 'editingId']);
        $this->vehicle_type = 'van';
        $this->capacity     = 4;
        $this->status       = 'available';
        $this->is_active    = true;
        $this->showModal    = true;
    }

    public function openEdit(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->editingId    = $id;
        $this->plate_number = $vehicle->plate_number;
        $this->make         = $vehicle->make;
        $this->model        = $vehicle->model;
        $this->vehicle_type = $vehicle->vehicle_type;
        $this->capacity     = $vehicle->capacity;
        $this->year         = $vehicle->year ?? '';
        $this->status       = $vehicle->status;
        $this->is_active    = $vehicle->is_active;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'plate_number' => strtoupper($this->plate_number),
            'make'         => $this->make,
            'model'        => $this->model,
            'vehicle_type' => $this->vehicle_type,
            'capacity'     => $this->capacity,
            'year'         => $this->year ?: null,
            'status'       => $this->status,
            'is_active'    => $this->is_active,
        ];

        if ($this->editingId) {
            $vehicle = Vehicle::findOrFail($this->editingId);
            $vehicle->update($data);
            ActivityLogService::log('vehicle_updated', "Vehicle {$vehicle->plate_number} updated.", $vehicle);
            $this->dispatch('toast', message: 'Vehicle updated.');
        } else {
            $vehicle = Vehicle::create($data);
            ActivityLogService::log('vehicle_created', "Vehicle {$vehicle->plate_number} added to fleet.", $vehicle);
            $this->dispatch('toast', message: 'Vehicle added to fleet.');
        }

        $this->showModal = false;
        unset($this->vehicles);
    }

    public function toggleActive(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->update(['is_active' => ! $vehicle->is_active]);
        unset($this->vehicles);
        $this->dispatch('toast', message: $vehicle->is_active ? 'Vehicle activated.' : 'Vehicle deactivated.');
    }

    public function render()
    {
        return view('livewire.admin.fleet-manager');
    }
}
