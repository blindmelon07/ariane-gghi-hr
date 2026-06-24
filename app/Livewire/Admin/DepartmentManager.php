<?php

namespace App\Livewire\Admin;

use App\Models\Department;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DepartmentManager extends Component
{
    use WithPagination;

    public string $search = '';

    // Create / Edit modal
    public bool    $showModal   = false;
    public ?int    $editId      = null;
    public string  $name        = '';
    public string  $code        = '';
    public string  $description = '';
    public bool    $isActive    = true;

    // Delete confirm
    public bool $showDeleteConfirm = false;
    public ?int $deleteId          = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function departments()
    {
        return Department::withCount('employees')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('code', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(20);
    }

    public function openCreate(): void
    {
        $this->reset('editId', 'name', 'code', 'description');
        $this->isActive  = true;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $dept = Department::findOrFail($id);

        $this->editId      = $dept->id;
        $this->name        = $dept->name;
        $this->code        = $dept->code ?? '';
        $this->description = $dept->description ?? '';
        $this->isActive    = $dept->is_active;
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate([
            'name'        => 'required|string|max:255|unique:departments,name,' . ($this->editId ?? 'NULL'),
            'code'        => 'nullable|string|max:50|unique:departments,code,' . ($this->editId ?? 'NULL'),
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name'        => trim($this->name),
            'code'        => trim($this->code) ?: null,
            'description' => trim($this->description) ?: null,
            'is_active'   => $this->isActive,
        ];

        if ($this->editId) {
            $dept = Department::findOrFail($this->editId);
            // Sync the department string on all linked employees when name changes
            if ($dept->name !== $data['name']) {
                $dept->employees()->update(['department' => $data['name']]);
            }
            $dept->update($data);
            session()->flash('success', 'Department updated.');
        } else {
            Department::create($data);
            session()->flash('success', 'Department created.');
        }

        $this->showModal = false;
        unset($this->departments);
    }

    public function confirmDelete(int $id): void
    {
        $this->deleteId          = $id;
        $this->showDeleteConfirm = true;
    }

    public function delete(): void
    {
        $dept = Department::withCount('employees')->find($this->deleteId);

        if ($dept && $dept->employees_count > 0) {
            session()->flash('error', "Cannot delete \"{$dept->name}\" — it has {$dept->employees_count} assigned employee(s). Reassign them first.");
            $this->reset('showDeleteConfirm', 'deleteId');

            return;
        }

        $dept?->delete();
        session()->flash('success', 'Department deleted.');
        $this->reset('showDeleteConfirm', 'deleteId');
        unset($this->departments);
    }

    public function render()
    {
        return view('livewire.admin.department-manager');
    }
}
