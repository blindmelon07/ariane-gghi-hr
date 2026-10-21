<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\SalaryDeductionRequest;
use App\Models\User;
use App\Notifications\HrAlert;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class SalaryDeductionForm extends Component
{
    use WithFileUploads;

    /** @var array<int, array{particular: string, description: string, amount: string, total: string, cutoff: string, starts_on: string}> */
    public array $lines = [];
    public $attachment = null;

    public function mount(): void
    {
        $this->lines = [$this->blankLine()];
    }

    private function blankLine(): array
    {
        return ['particular' => '', 'description' => '', 'amount' => '', 'total' => '', 'cutoff' => 'both', 'starts_on' => now()->toDateString()];
    }

    public function addLine(): void
    {
        if (\count($this->lines) < \count(SalaryDeductionRequest::PARTICULARS)) {
            $this->lines[] = $this->blankLine();
        }
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines) ?: [$this->blankLine()];
    }

    public static function isLoan(?string $particular): bool
    {
        return (bool) (SalaryDeductionRequest::PARTICULARS[$particular][2] ?? false);
    }

    public static function needsDescription(?string $particular): bool
    {
        return (bool) (SalaryDeductionRequest::PARTICULARS[$particular][3] ?? false);
    }

    #[Computed]
    public function needsAttachment(): bool
    {
        return collect($this->lines)->contains(fn ($l) => \in_array($l['particular'], SalaryDeductionRequest::NEEDS_AMORTIZATION, true));
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    #[Computed]
    public function myRequests()
    {
        if (! $this->employee) return collect();

        return SalaryDeductionRequest::with('lines')
            ->where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function submit(): void
    {
        $rules = [
            'lines'               => 'required|array|min:1',
            'lines.*.particular'  => ['required', Rule::in(array_keys(SalaryDeductionRequest::PARTICULARS))],
            'lines.*.amount'      => 'required|numeric|min:1|max:999999',
            'lines.*.cutoff'      => 'required|in:both,1st,2nd',
            'lines.*.starts_on'   => 'required|date|after_or_equal:today',
            'lines.*.description' => 'nullable|string|max:120',
            'lines.*.total'       => 'nullable|numeric|max:9999999',
            'attachment'          => ($this->needsAttachment ? 'required' : 'nullable') . '|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        $this->validate($rules, [
            'attachment.required' => 'For loan deductions, please attach the amortization table issued by the bank/agency.',
        ], [
            'lines.*.particular' => 'particular',
            'lines.*.amount'     => 'amount',
            'lines.*.cutoff'     => 'cut-off',
            'lines.*.starts_on'  => 'start date',
        ]);

        foreach ($this->lines as $i => $line) {
            if (self::needsDescription($line['particular']) && trim($line['description']) === '') {
                $this->addError("lines.{$i}.description", 'Please specify.');
                return;
            }
            if (self::isLoan($line['particular'])) {
                if ($line['total'] === '' || (float) $line['total'] < (float) $line['amount']) {
                    $this->addError("lines.{$i}.total", 'Enter the total amount to be paid (at least the amount per cut-off).');
                    return;
                }
            }
        }

        $employee = $this->employee;
        if (! $employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        $path = $this->attachment?->store("salary-deductions/{$employee->id}", 'local');

        $request = DB::transaction(function () use ($employee, $path) {
            $request = SalaryDeductionRequest::create([
                'employee_id'     => $employee->id,
                'attachment_path' => $path,
                'status'          => 'pending',
                'approval_step'   => 1,
            ]);

            foreach ($this->lines as $line) {
                $request->lines()->create([
                    'particular'        => $line['particular'],
                    'description'       => self::needsDescription($line['particular']) ? trim($line['description']) : null,
                    'amount_per_cutoff' => $line['amount'],
                    'total_amount'      => self::isLoan($line['particular']) ? $line['total'] : null,
                    'cutoff_schedule'   => $line['cutoff'],
                    'starts_on'         => $line['starts_on'],
                ]);
            }

            return $request;
        });

        ActivityLogService::log('salary_deduction_filed', "Salary deduction form #{$request->id} filed by {$employee->full_name}", $employee);

        $hr = User::where('role', 'hr_admin')->where('is_active', true)->get();
        Notification::send($hr, new HrAlert(
            'Salary deduction request — ' . $employee->full_name,
            \count($this->lines) . ' item(s), ₱' . number_format($request->load('lines')->totalPerCutoff(), 2) . ' per cut-off.',
            route('admin.salary-deductions'),
            'request',
            "salary-deduction:{$request->id}:filed",
        ));

        $this->lines      = [$this->blankLine()];
        $this->attachment = null;
        unset($this->myRequests, $this->needsAttachment);

        session()->flash('success', 'Salary deduction request submitted. Once HR approves it, the deductions start automatically on payroll.');
    }

    public function cancel(int $id): void
    {
        $request = SalaryDeductionRequest::where('employee_id', $this->employee?->id)->find($id);

        if ($request && $request->status === 'pending') {
            $request->update(['status' => 'cancelled', 'approval_step' => null]);
            unset($this->myRequests);
            session()->flash('success', 'Salary deduction request cancelled.');
        }
    }

    public function render()
    {
        return view('livewire.employee.salary-deduction-form');
    }
}
