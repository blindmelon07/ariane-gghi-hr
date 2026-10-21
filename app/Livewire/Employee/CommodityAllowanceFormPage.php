<?php

namespace App\Livewire\Employee;

use App\Models\CommodityAllowanceForm;
use App\Models\Employee;
use App\Services\ActivityLogService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CommodityAllowanceFormPage extends Component
{
    public string $lastName        = '';
    public string $firstName       = '';
    public string $middleName      = '';
    public string $position        = '';
    public string $address         = '';
    public string $religion        = '';
    public string $birthdate       = '';
    public string $civilStatus     = '';
    public string $spouseName      = '';
    public string $spouseBirthdate = '';
    public string $gsacMembership  = '';
    public string $passbookNumber  = '';
    public string $signatureName   = '';
    public bool   $agree           = false;

    public function mount(): void
    {
        abort_unless(CommodityAllowanceForm::appliesTo($this->employee), 403);

        if ($form = $this->existing) {
            $this->lastName        = $form->last_name;
            $this->firstName       = $form->first_name;
            $this->middleName      = $form->middle_name ?? '';
            $this->position        = $form->position;
            $this->address         = $form->address;
            $this->religion        = $form->religion ?? '';
            $this->birthdate       = $form->birthdate->toDateString();
            $this->civilStatus     = $form->civil_status;
            $this->spouseName      = $form->spouse_name ?? '';
            $this->spouseBirthdate = $form->spouse_birthdate?->toDateString() ?? '';
            $this->gsacMembership  = $form->gsac_membership;
            $this->passbookNumber  = $form->passbook_number ?? '';
            $this->signatureName   = $form->signature_name;
            return;
        }

        // Prefill from the employee record. BioTime imports can pack
        // "Last,First[,Middle]" into first_name with last_name empty.
        $emp   = $this->employee;
        $first = trim((string) $emp->first_name);
        $last  = trim((string) $emp->last_name);
        if ($last === '' && str_contains($first, ',')) {
            $parts            = array_map('trim', explode(',', $first));
            $this->lastName   = ucwords(strtolower($parts[0] ?? ''));
            $this->firstName  = ucwords(strtolower($parts[1] ?? ''));
            $this->middleName = ucwords(strtolower($parts[2] ?? ''));
        } else {
            $this->lastName  = $last;
            $this->firstName = $first;
        }
        $this->position  = (string) $emp->position;
        $this->birthdate = $emp->date_of_birth?->toDateString() ?? '';
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return CommodityAllowanceForm::employeeForCurrentUser();
    }

    #[Computed]
    public function existing(): ?CommodityAllowanceForm
    {
        return $this->employee ? CommodityAllowanceForm::where('employee_id', $this->employee->id)->first() : null;
    }

    public function save(): void
    {
        abort_unless(CommodityAllowanceForm::appliesTo($this->employee), 403);

        $married = $this->civilStatus === 'married';
        $member  = \in_array($this->gsacMembership, ['regular', 'associate'], true);

        $this->validate([
            'lastName'        => 'required|string|max:255',
            'firstName'       => 'required|string|max:255',
            'middleName'      => 'nullable|string|max:255',
            'position'        => 'required|string|max:255',
            'address'         => 'required|string|max:255',
            'religion'        => 'nullable|string|max:100',
            'birthdate'       => 'required|date|before:today',
            'civilStatus'     => ['required', Rule::in(array_keys(CommodityAllowanceForm::CIVIL_STATUSES))],
            'spouseName'      => [$married ? 'required' : 'nullable', 'string', 'max:255'],
            'spouseBirthdate' => [$married ? 'required' : 'nullable', 'date', 'before:today'],
            'gsacMembership'  => ['required', Rule::in(array_keys(CommodityAllowanceForm::MEMBERSHIPS))],
            'passbookNumber'  => [$member ? 'required' : 'nullable', 'string', 'max:50'],
            'signatureName'   => 'required|string|max:255',
            'agree'           => 'accepted',
        ], [
            'agree.accepted'          => 'Please confirm that the information is true and correct.',
            'spouseName.required'     => 'Enter your spouse\'s name.',
            'spouseBirthdate.required' => 'Enter your spouse\'s birthdate.',
            'passbookNumber.required' => 'GSAC members need to enter their passbook number.',
        ]);

        $wasNew = ! $this->existing;

        CommodityAllowanceForm::updateOrCreate(
            ['employee_id' => $this->employee->id],
            [
                'last_name'        => $this->lastName,
                'first_name'       => $this->firstName,
                'middle_name'      => $this->middleName ?: null,
                'position'         => $this->position,
                'address'          => $this->address,
                'religion'         => $this->religion ?: null,
                'birthdate'        => $this->birthdate,
                'civil_status'     => $this->civilStatus,
                'spouse_name'      => $married ? $this->spouseName : null,
                'spouse_birthdate' => $married ? $this->spouseBirthdate : null,
                'gsac_membership'  => $this->gsacMembership,
                'passbook_number'  => $member ? $this->passbookNumber : null,
                'signature_name'   => $this->signatureName,
                'submitted_at'     => now(),
            ]
        );

        ActivityLogService::log('commodity_form_' . ($wasNew ? 'submitted' : 'updated'), "Commodity allowance form " . ($wasNew ? 'submitted' : 'updated') . " by {$this->employee->full_name}", $this->employee);

        $this->agree = false;
        unset($this->existing);
        session()->flash('success', $wasNew ? 'Thank you! Your Commodity Allowance Form has been submitted to HR.' : 'Your Commodity Allowance Form has been updated.');
        $this->redirectRoute('commodity-form', navigate: true);
    }

    public function render()
    {
        return view('livewire.employee.commodity-allowance-form-page');
    }
}
