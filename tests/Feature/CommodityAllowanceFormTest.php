<?php

use App\Livewire\Employee\CommodityAllowanceFormPage;
use App\Models\CommodityAllowanceForm;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function caUser(string $role, ?string $code = null): User
{
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    $user = User::factory()->create(array_filter(['role' => $role, 'employee_code' => $code]));
    $user->syncRoles($role);
    return $user;
}

function caEmployee(string $code, string $type = 'regular', array $extra = []): Employee
{
    return Employee::create(array_merge([
        'emp_code' => $code, 'first_name' => 'Maria', 'last_name' => 'Santos', 'is_active' => true,
        'employment_type' => $type, 'date_of_birth' => '1990-05-10',
    ], $extra));
}

function caFill($component)
{
    return $component
        ->set('position', 'Staff Nurse')
        ->set('address', 'Brgy. Bibincahan, Sorsogon City')
        ->set('religion', 'Roman Catholic')
        ->set('civilStatus', 'single')
        ->set('gsacMembership', 'non_member')
        ->set('signatureName', 'Maria Santos')
        ->set('agree', true);
}

it('prompts regular employees until they submit, and never probationary ones', function () {
    $regular = caUser('employee', 'CA1');
    caEmployee('CA1');
    $prob = caUser('employee', 'CA2');
    caEmployee('CA2', 'probationary');

    $this->actingAs($regular)->get('/dashboard')->assertSee('Commodity Allowance Form');
    $this->actingAs($prob)->get('/dashboard')->assertDontSee('Commodity Allowance');
    $this->actingAs($prob)->get('/commodity-allowance-form')->assertForbidden();

    caFill(Livewire::actingAs($regular)->test(CommodityAllowanceFormPage::class))->call('save')->assertHasNoErrors();

    $this->actingAs($regular)->get('/dashboard')->assertDontSee('please fill in your Commodity Allowance Form');
});

it('prefills from the employee record, including BioTime "Last,First,Middle" names', function () {
    $user = caUser('employee', 'CA3');
    caEmployee('CA3', 'regular', ['first_name' => 'DELA CRUZ,JUAN,REYES', 'last_name' => '']);

    Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class)
        ->assertSet('lastName', 'Dela Cruz')
        ->assertSet('firstName', 'Juan')
        ->assertSet('middleName', 'Reyes')
        ->assertSet('birthdate', '1990-05-10');
});

it('requires spouse details when married and a passbook number for GSAC members', function () {
    $user = caUser('employee', 'CA4');
    $emp  = caEmployee('CA4');

    caFill(Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class))
        ->set('civilStatus', 'married')
        ->set('gsacMembership', 'regular')
        ->call('save')
        ->assertHasErrors(['spouseName', 'spouseBirthdate', 'passbookNumber']);

    caFill(Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class))
        ->set('civilStatus', 'married')
        ->set('spouseName', 'Pedro Santos')
        ->set('spouseBirthdate', '1988-01-02')
        ->set('gsacMembership', 'associate')
        ->set('passbookNumber', 'PB-1234')
        ->call('save')
        ->assertHasNoErrors();

    $form = CommodityAllowanceForm::where('employee_id', $emp->id)->sole();
    expect($form->spouse_name)->toBe('Pedro Santos')->and($form->passbook_number)->toBe('PB-1234');
});

it('updates the same form instead of creating another', function () {
    $user = caUser('employee', 'CA5');
    caEmployee('CA5');

    caFill(Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class))->call('save');
    Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class)
        ->assertSet('address', 'Brgy. Bibincahan, Sorsogon City')
        ->set('address', 'New address')
        ->set('agree', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(CommodityAllowanceForm::count())->toBe(1)->and(CommodityAllowanceForm::first()->address)->toBe('New address');
});

it('lets HR see who submitted and open the form; other employees cannot', function () {
    $user = caUser('employee', 'CA6');
    caEmployee('CA6');
    caEmployee('CA7');   // has not submitted
    caFill(Livewire::actingAs($user)->test(CommodityAllowanceFormPage::class))->call('save');
    $form = CommodityAllowanceForm::sole();

    $hr = caUser('hr_admin');
    $this->actingAs($hr)->get('/admin/commodity-allowance-forms')->assertOk()->assertSee('Not yet submitted');
    $this->actingAs($hr)->get("/commodity-allowance-form/{$form->id}")->assertOk()->assertSee('Bibincahan');
    $this->actingAs($user)->get("/commodity-allowance-form/{$form->id}")->assertOk();
    $this->actingAs(caUser('employee', 'CA8'))->get("/commodity-allowance-form/{$form->id}")->assertForbidden();
});
