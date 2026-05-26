<?php

use App\Livewire\Admin\DeductionManager;
use App\Models\DeductionType;
use App\Models\Employee;
use App\Models\OtherDeduction;
use App\Models\User;
use Livewire\Livewire;

// ── Helpers ────────────────────────────────────────────────────────────────

function adminUser(): User
{
    return User::factory()->create(['role' => 'hr_admin']);
}

function testEmployee(): Employee
{
    static $counter = 0;
    $counter++;
    return Employee::create([
        'emp_code'   => "EMP{$counter}",
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'is_active'  => true,
    ]);
}

function testDeductionType(array $attrs = []): DeductionType
{
    static $counter = 0;
    $counter++;
    return DeductionType::create(array_merge([
        'code'      => "TYPE{$counter}",
        'name'      => "Type {$counter}",
        'category'  => 'other',
        'is_active' => true,
    ], $attrs));
}

function testDeduction(Employee $emp, DeductionType $type, array $attrs = []): OtherDeduction
{
    return OtherDeduction::create(array_merge([
        'employee_id'       => $emp->id,
        'deduction_type_id' => $type->id,
        'description'       => 'Test Deduction',
        'amount_per_cutoff' => 500.00,
        'remaining_balance' => 5000.00,
        'is_active'         => true,
    ], $attrs));
}

// ── Page access ────────────────────────────────────────────────────────────

describe('page access', function () {
    it('allows hr_admin to load the deductions page', function () {
        $this->actingAs(adminUser())
            ->get('/admin/deductions')
            ->assertOk();
    });

    it('redirects unauthenticated users to login', function () {
        $this->get('/admin/deductions')
            ->assertRedirect('/login');
    });

    it('blocks employee role from the deductions page', function () {
        $user = User::factory()->create(['role' => 'employee']);
        $this->actingAs($user)
            ->get('/admin/deductions')
            ->assertForbidden();
    });
});

// ── Deduction Type management ──────────────────────────────────────────────

describe('deduction type: modal open', function () {
    it('opens the add-type modal with a blank form', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAddType')
            ->assertSet('showTypeModal', true)
            ->assertSet('editTypeId', null)
            ->assertSet('typeCode', '')
            ->assertSet('typeName', '')
            ->assertSet('typeCategory', 'other');
    });

    it('opens the edit-type modal with the correct data', function () {
        $type = testDeductionType(['code' => 'SSS', 'name' => 'SSS Contribution', 'category' => 'government']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openEditType', $type->id)
            ->assertSet('showTypeModal', true)
            ->assertSet('editTypeId', $type->id)
            ->assertSet('typeCode', 'SSS')
            ->assertSet('typeName', 'SSS Contribution')
            ->assertSet('typeCategory', 'government');
    });
});

describe('deduction type: save', function () {
    it('creates a new deduction type and closes modal', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAddType')
            ->set('typeCode', 'loan01')
            ->set('typeName', 'Car Loan')
            ->set('typeCategory', 'loan')
            ->call('saveType')
            ->assertSet('showTypeModal', false)
            ->assertHasNoErrors();

        expect(DeductionType::where('code', 'LOAN01')->exists())->toBeTrue();
    });

    it('saves the type code as uppercase', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->set('typeCode', 'lowercase')
            ->set('typeName', 'Any Type')
            ->set('typeCategory', 'other')
            ->call('saveType');

        expect(DeductionType::where('code', 'LOWERCASE')->exists())->toBeTrue();
    });

    it('updates an existing deduction type', function () {
        $type = testDeductionType(['name' => 'Old Name', 'category' => 'other']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openEditType', $type->id)
            ->set('typeName', 'Updated Name')
            ->set('typeCategory', 'benefit')
            ->call('saveType')
            ->assertSet('showTypeModal', false);

        expect($type->fresh()->name)->toBe('Updated Name');
        expect($type->fresh()->category)->toBe('benefit');
    });

    it('requires typeCode and typeName to save', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAddType')
            ->call('saveType')
            ->assertHasErrors(['typeCode', 'typeName']);
    });

    it('rejects a duplicate type code', function () {
        testDeductionType(['code' => 'DUPE', 'name' => 'Existing']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAddType')
            ->set('typeCode', 'DUPE')
            ->set('typeName', 'Another')
            ->set('typeCategory', 'other')
            ->call('saveType')
            ->assertHasErrors(['typeCode']);
    });

    it('allows the same code when editing its own record', function () {
        $type = testDeductionType(['code' => 'KEEP', 'name' => 'Original']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openEditType', $type->id)
            ->set('typeName', 'Renamed')
            ->call('saveType')
            ->assertHasNoErrors();

        expect($type->fresh()->name)->toBe('Renamed');
    });

    it('rejects an invalid category value', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAddType')
            ->set('typeCode', 'XYZ')
            ->set('typeName', 'Some')
            ->set('typeCategory', 'invalid')
            ->call('saveType')
            ->assertHasErrors(['typeCategory']);
    });
});

describe('deduction type: toggle active', function () {
    it('deactivates an active type', function () {
        $type = testDeductionType(['is_active' => true]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('toggleTypeActive', $type->id);

        expect($type->fresh()->is_active)->toBeFalse();
    });

    it('reactivates an inactive type', function () {
        $type = testDeductionType(['is_active' => false]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('toggleTypeActive', $type->id);

        expect($type->fresh()->is_active)->toBeTrue();
    });
});

// ── Deduction management ───────────────────────────────────────────────────

describe('deduction: modal open', function () {
    it('opens the add-deduction modal with a blank form', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAdd')
            ->assertSet('showModal', true)
            ->assertSet('editDeductionId', null)
            ->assertSet('modalEmployeeId', null)
            ->assertSet('description', '')
            ->assertSet('amountPerCutoff', '');
    });

    it('opens the edit-deduction modal with the correct data', function () {
        $emp  = testEmployee();
        $type = testDeductionType();
        $ded  = testDeduction($emp, $type, [
            'description'       => 'Laptop Loan',
            'amount_per_cutoff' => 300.00,
            'remaining_balance' => 3000.00,
            'is_active'         => true,
        ]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openEdit', $ded->id)
            ->assertSet('showModal', true)
            ->assertSet('editDeductionId', $ded->id)
            ->assertSet('modalEmployeeId', $emp->id)
            ->assertSet('description', 'Laptop Loan')
            ->assertSet('amountPerCutoff', '300.00')
            ->assertSet('remainingBalance', '3000.00')
            ->assertSet('isActive', true);
    });
});

describe('deduction: save', function () {
    it('creates a new deduction and closes modal', function () {
        $emp  = testEmployee();
        $type = testDeductionType();

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAdd')
            ->set('modalEmployeeId', $emp->id)
            ->set('deductionTypeId', (string) $type->id)
            ->set('description', 'Phone Loan')
            ->set('amountPerCutoff', '200')
            ->set('remainingBalance', '2000')
            ->call('save')
            ->assertSet('showModal', false)
            ->assertHasNoErrors();

        expect(OtherDeduction::where('description', 'Phone Loan')->exists())->toBeTrue();
    });

    it('updates an existing deduction', function () {
        $emp  = testEmployee();
        $type = testDeductionType();
        $ded  = testDeduction($emp, $type, ['description' => 'Old Desc', 'amount_per_cutoff' => 100, 'remaining_balance' => 1000]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openEdit', $ded->id)
            ->set('description', 'New Desc')
            ->set('amountPerCutoff', '150')
            ->call('save')
            ->assertSet('showModal', false);

        expect($ded->fresh()->description)->toBe('New Desc');
        expect((float) $ded->fresh()->amount_per_cutoff)->toBe(150.0);
    });

    it('requires employee, type, description, and amount to save', function () {
        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAdd')
            ->call('save')
            ->assertHasErrors(['modalEmployeeId', 'deductionTypeId', 'description', 'amountPerCutoff']);
    });

    it('requires remaining balance only for loan types', function () {
        $loanType = testDeductionType(['category' => 'loan']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAdd')
            ->set('deductionTypeId', (string) $loanType->id)
            ->set('remainingBalance', '')
            ->call('save')
            ->assertHasErrors(['remainingBalance']);
    });

    it('does not require remaining balance for government contribution types', function () {
        $emp  = testEmployee();
        $type = testDeductionType(['category' => 'government']);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('openAdd')
            ->set('modalEmployeeId', $emp->id)
            ->set('deductionTypeId', (string) $type->id)
            ->set('description', 'Pag-IBIG Contribution')
            ->set('amountPerCutoff', '100')
            ->call('save')
            ->assertHasNoErrors(['remainingBalance'])
            ->assertSet('showModal', false);
    });

    it('rejects a non-existent employee id', function () {
        $type = testDeductionType();

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->set('modalEmployeeId', 9999)
            ->set('deductionTypeId', (string) $type->id)
            ->set('description', 'Desc')
            ->set('amountPerCutoff', '100')
            ->set('remainingBalance', '1000')
            ->call('save')
            ->assertHasErrors(['modalEmployeeId']);
    });
});

describe('deduction: toggle and delete', function () {
    it('deactivates an active deduction', function () {
        $emp  = testEmployee();
        $type = testDeductionType();
        $ded  = testDeduction($emp, $type, ['is_active' => true]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('toggleActive', $ded->id);

        expect($ded->fresh()->is_active)->toBeFalse();
    });

    it('reactivates an inactive deduction', function () {
        $emp  = testEmployee();
        $type = testDeductionType();
        $ded  = testDeduction($emp, $type, ['is_active' => false]);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('toggleActive', $ded->id);

        expect($ded->fresh()->is_active)->toBeTrue();
    });

    it('deletes a deduction from the database', function () {
        $emp  = testEmployee();
        $type = testDeductionType();
        $ded  = testDeduction($emp, $type);

        Livewire::actingAs(adminUser())
            ->test(DeductionManager::class)
            ->call('delete', $ded->id);

        expect(OtherDeduction::find($ded->id))->toBeNull();
    });
});
