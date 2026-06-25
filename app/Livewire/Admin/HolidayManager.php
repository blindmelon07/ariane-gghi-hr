<?php

namespace App\Livewire\Admin;

use App\Models\Holiday;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class HolidayManager extends Component
{
    use WithPagination;

    public string $filterYear = '';

    public bool    $showModal    = false;
    public ?int    $editId       = null;
    public string  $date         = '';
    public string  $name         = '';
    public string  $type         = 'regular';
    public bool    $is_recurring = false;

    public function mount(): void
    {
        $this->filterYear = (string) now()->year;
    }

    public function updatedFilterYear(): void { $this->resetPage(); }

    #[Computed]
    public function holidays()
    {
        return Holiday::when($this->filterYear, fn ($q) => $q->whereYear('date', $this->filterYear))
            ->orderBy('date')
            ->paginate(20);
    }

    public function openAdd(): void
    {
        $this->reset(['editId', 'date', 'name', 'type', 'is_recurring']);
        $this->type = 'regular';
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $holiday = Holiday::findOrFail($id);
        $this->editId       = $holiday->id;
        $this->date         = $holiday->date->format('Y-m-d');
        $this->name         = $holiday->name;
        $this->type         = $holiday->type;
        $this->is_recurring = $holiday->is_recurring;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate([
            'date'         => 'required|date',
            'name'         => 'required|string|max:100',
            'type'         => 'required|in:regular,special_non_working,special_working',
            'is_recurring' => 'boolean',
        ]);

        if ($this->editId) {
            $holiday = Holiday::findOrFail($this->editId);
            $holiday->update([
                'date'         => $this->date,
                'name'         => $this->name,
                'type'         => $this->type,
                'is_recurring' => $this->is_recurring,
            ]);
            ActivityLogService::log('holiday_updated', "Holiday updated: {$this->name} on {$this->date}");
            session()->flash('success', "Holiday \"{$this->name}\" updated.");
        } else {
            $exists = Holiday::whereDate('date', $this->date)
                ->where('name', $this->name)
                ->exists();

            if ($exists) {
                $this->addError('name', 'A holiday with this name already exists on this date.');
                return;
            }

            Holiday::create([
                'date'         => $this->date,
                'name'         => $this->name,
                'type'         => $this->type,
                'is_recurring' => $this->is_recurring,
                'created_by'   => auth()->id(),
            ]);
            ActivityLogService::log('holiday_created', "Holiday created: {$this->name} on {$this->date}");
            session()->flash('success', "Holiday \"{$this->name}\" added.");
        }

        $this->showModal = false;
        unset($this->holidays);
    }

    public function delete(int $id): void
    {
        $holiday = Holiday::findOrFail($id);
        $name    = $holiday->name;
        $holiday->delete();
        ActivityLogService::log('holiday_deleted', "Holiday deleted: {$name}");
        unset($this->holidays);
        session()->flash('success', "Holiday \"{$name}\" removed.");
    }

    public function render()
    {
        return view('livewire.admin.holiday-manager');
    }
}
