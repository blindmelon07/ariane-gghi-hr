<?php

namespace App\Livewire\Admin;

use App\Models\Driver;
use App\Models\Employee;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class DriverManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|exists:employees,id')]
    public string $employee_id = '';

    #[Validate('required|string|max:30')]
    public string $license_number = '';

    #[Validate('nullable|date')]
    public string $license_expiry = '';

    #[Validate('nullable|date')]
    public string $medical_clearance_date = '';

    public bool $is_active = true;

    public function updatedSearch(): void { $this->resetPage(); }

    #[Computed]
    public function drivers()
    {
        return Driver::with('employee')
            ->when($this->search, fn ($q) => $q->whereHas('employee', fn ($eq) => $eq
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('emp_code', 'like', "%{$this->search}%")))
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    #[Computed]
    public function employees()
    {
        $existingIds = Driver::pluck('employee_id')->toArray();
        if ($this->editingId) {
            $current = Driver::find($this->editingId)?->employee_id;
            $existingIds = array_diff($existingIds, [$current]);
        }
        return Employee::whereNotIn('id', $existingIds)->orderBy('first_name')->get();
    }

    public function openCreate(): void
    {
        $this->reset(['employee_id', 'license_number', 'license_expiry', 'medical_clearance_date', 'editingId']);
        $this->is_active = true;
        $this->showModal = true;
        unset($this->employees);
    }

    public function openEdit(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $this->editingId              = $id;
        $this->employee_id            = $driver->employee_id;
        $this->license_number         = $driver->license_number;
        $this->license_expiry         = $driver->license_expiry?->format('Y-m-d') ?? '';
        $this->medical_clearance_date = $driver->medical_clearance_date?->format('Y-m-d') ?? '';
        $this->is_active              = $driver->is_active;
        $this->showModal              = true;
        unset($this->employees);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'employee_id'            => $this->employee_id,
            'license_number'         => $this->license_number,
            'license_expiry'         => $this->license_expiry ?: null,
            'medical_clearance_date' => $this->medical_clearance_date ?: null,
            'is_active'              => $this->is_active,
        ];

        if ($this->editingId) {
            $driver = Driver::findOrFail($this->editingId);
            $driver->update($data);
            ActivityLogService::log('driver_updated', "Driver record updated for employee #{$driver->employee_id}.", $driver);
            $this->dispatch('toast', message: 'Driver record updated.');
        } else {
            $driver = Driver::create($data);
            ActivityLogService::log('driver_created', "Driver record created for employee #{$driver->employee_id}.", $driver);
            $this->dispatch('toast', message: 'Driver added.');
        }

        $this->showModal = false;
        unset($this->drivers, $this->employees);
    }

    public function toggleActive(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $driver->update(['is_active' => ! $driver->is_active]);
        unset($this->drivers);
        $this->dispatch('toast', message: $driver->is_active ? 'Driver activated.' : 'Driver deactivated.');
    }

    public function render()
    {
        return view('livewire.admin.driver-manager');
    }
}
