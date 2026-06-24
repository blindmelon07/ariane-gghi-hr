<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use App\Models\Position;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PositionManager extends Component
{
    use WithPagination;

    public string $search     = '';
    public string $filterDept = '';

    // Create / Edit modal
    public bool   $showModal    = false;
    public ?int   $editId       = null;
    public string $name         = '';
    public ?int   $departmentId = null;
    public bool   $isActive     = true;
    public bool   $isApprover   = false;

    // Delete confirm
    public bool $showDeleteConfirm = false;
    public ?int $deleteId          = null;

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedFilterDept(): void { $this->resetPage(); }

    #[Computed]
    public function positions()
    {
        return Position::with('department')
            ->withCount('employees')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterDept, fn ($q) => $q->where('department_id', $this->filterDept))
            ->orderBy('name')
            ->paginate(20);
    }

    #[Computed]
    public function departments()
    {
        return Department::where('is_active', true)->orderBy('name')->get();
    }

    public function openCreate(): void
    {
        $this->reset('editId', 'name', 'departmentId');
        $this->isActive   = true;
        $this->isApprover = false;
        $this->showModal  = true;
    }

    public function openEdit(int $id): void
    {
        $pos = Position::findOrFail($id);

        $this->editId       = $pos->id;
        $this->name         = $pos->name;
        $this->departmentId = $pos->department_id;
        $this->isActive     = $pos->is_active;
        $this->isApprover   = $pos->is_approver;
        $this->showModal    = true;
    }

    public function toggleApprover(int $id): void
    {
        $pos = Position::findOrFail($id);
        $pos->update(['is_approver' => ! $pos->is_approver]);
        unset($this->positions);
    }

    public function save(): void
    {
        $this->validate([
            'name'         => 'required|string|max:255',
            'departmentId' => 'nullable|exists:departments,id',
        ]);

        $data = [
            'name'          => trim($this->name),
            'department_id' => $this->departmentId,
            'is_active'     => $this->isActive,
            'is_approver'   => $this->isApprover,
        ];

        if ($this->editId) {
            Position::findOrFail($this->editId)->update($data);
            session()->flash('success', 'Position updated.');
        } else {
            Position::create($data);
            session()->flash('success', 'Position created.');
        }

        $this->showModal = false;
        unset($this->positions);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $pos = Position::withCount('employees')->find($this->deleteId);

        if ($pos && $pos->employees_count > 0) {
            session()->flash('error', "Cannot delete \"{$pos->name}\" — {$pos->employees_count} employee(s) are assigned to it.");
            $this->reset('showDeleteConfirm', 'deleteId');

            return;
        }

        $pos?->delete();
        session()->flash('success', 'Position deleted.');
        $this->reset('showDeleteConfirm', 'deleteId');
        unset($this->positions);
    }

    public function render()
    {
        return view('livewire.admin.position-manager');
    }
}
