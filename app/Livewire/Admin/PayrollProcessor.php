<?php

namespace App\Livewire\Admin;

use App\Exports\PayrollExport;
use App\Jobs\GeneratePayslipsJob;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\ActivityLogService;
use App\Services\PayrollWorksheetImportService;
use App\Services\SmsNotificationService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PayrollProcessor extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $filterStatus = 'all';

    // Create period form
    public bool   $showCreate             = false;
    public string $cutoffType             = 'custom';
    public string $customDeductionScope   = 'both';
    public string $startDate              = '';
    public string $endDate                = '';
    public string $periodName             = '';

    // Per-period payslip viewer
    public ?int   $viewPeriodId  = null;

    // Generate payslips — pick which employees to process
    public ?int  $generatePeriodId    = null;
    public array $selectedEmployeeIds = [];

    // Excel import
    public ?int    $importPeriodId = null;

    public mixed   $importFile     = null;

    public function mount(): void
    {
        $this->prefillDates();
    }

    public function prefillDates(): void
    {
        $now = now();

        $this->customDeductionScope = 'both';

        if ($now->day <= 15) {
            $this->cutoffType = 'semi_monthly_1';
            $this->startDate  = $now->copy()->startOfMonth()->toDateString();
            $this->endDate    = $now->copy()->startOfMonth()->addDays(14)->toDateString();
        } else {
            $this->cutoffType = 'semi_monthly_2';
            $this->startDate  = $now->copy()->startOfMonth()->addDays(15)->toDateString();
            $this->endDate    = $now->copy()->endOfMonth()->toDateString();
        }

        $this->periodName = $now->format('F Y') . ' - ' . ($this->cutoffType === 'semi_monthly_1' ? '1st Half' : '2nd Half');
    }

    public function setCutoffType(string $type): void
    {
        $this->cutoffType = $type;
        $this->updatedCutoffType();
    }

    public function updatedCutoffType(): void
    {
        // Custom: clear dates and let the user pick freely — no auto-fill
        if ($this->cutoffType === 'custom') {
            $this->startDate            = '';
            $this->endDate              = '';
            $this->periodName           = '';
            $this->customDeductionScope = 'both';
            return;
        }

        $now = now();

        match ($this->cutoffType) {
            'semi_monthly_1' => [
                $this->startDate  = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate    = $now->copy()->startOfMonth()->addDays(14)->toDateString(),
                $this->periodName = $now->format('F Y') . ' - 1st Half',
            ],
            'semi_monthly_2' => [
                $this->startDate  = $now->copy()->startOfMonth()->addDays(15)->toDateString(),
                $this->endDate    = $now->copy()->endOfMonth()->toDateString(),
                $this->periodName = $now->format('F Y') . ' - 2nd Half',
            ],
            'monthly' => [
                $this->startDate  = $now->copy()->startOfMonth()->toDateString(),
                $this->endDate    = $now->copy()->endOfMonth()->toDateString(),
                $this->periodName = $now->format('F Y') . ' - Monthly',
            ],
            default => null,
        };
    }

    #[Computed]
    public function periods()
    {
        return PayrollPeriod::query()
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('start_date')
            ->paginate(10);
    }

    public function createPeriod(): void
    {
        $this->validate([
            'periodName'           => 'required|string|max:255',
            'cutoffType'           => 'required|in:semi_monthly_1,semi_monthly_2,monthly,custom',
            'customDeductionScope' => 'required|in:both,1st,2nd,all',
            'startDate'            => 'required|date',
            'endDate'              => 'required|date|after_or_equal:startDate',
        ]);

        PayrollPeriod::create([
            'name'                   => $this->periodName,
            'cutoff_type'            => $this->cutoffType,
            'custom_deduction_scope' => $this->cutoffType === 'custom' ? $this->customDeductionScope : 'both',
            'start_date'             => $this->startDate,
            'end_date'               => $this->endDate,
            'status'                 => 'draft',
        ]);

        $this->showCreate = false;
        $this->prefillDates();
        unset($this->periods);
        session()->flash('success', 'Payroll period created.');
    }

    public function openGenerate(int $periodId): void
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status === 'finalized') {
            session()->flash('error', 'Cannot regenerate a finalized payroll.');
            return;
        }

        $this->generatePeriodId    = $periodId;
        $this->selectedEmployeeIds = $this->eligibleEmployees->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->resetErrorBag('selectedEmployeeIds');
    }

    public function closeGenerate(): void
    {
        $this->generatePeriodId    = null;
        $this->selectedEmployeeIds = [];
    }

    public function generatePayslips(): void
    {
        $this->validate(
            ['selectedEmployeeIds' => 'required|array|min:1'],
            ['selectedEmployeeIds.required' => 'Select at least one employee.', 'selectedEmployeeIds.min' => 'Select at least one employee.'],
        );

        $period = PayrollPeriod::findOrFail($this->generatePeriodId);

        if ($period->status === 'finalized') {
            session()->flash('error', 'Cannot regenerate a finalized payroll.');
            $this->closeGenerate();
            return;
        }

        $employeeIds = array_map('intval', $this->selectedEmployeeIds);
        $count       = count($employeeIds);

        // Selecting everyone is the same as the old "all employees" run.
        $isAll = $count >= $this->eligibleEmployees->count();

        $period->update(['status' => 'processing']);
        GeneratePayslipsJob::dispatch($period->id, $isAll ? null : $employeeIds);

        ActivityLogService::log(
            'payroll_generated',
            "Generated payslips for {$count} employee(s): {$period->name}",
            $period
        );

        $this->closeGenerate();
        unset($this->periods);
        session()->flash('success', "Payslip generation queued for {$count} employee(s). Refresh in a moment to see results.");
    }

    #[Computed]
    public function eligibleEmployees()
    {
        return Employee::where('is_active', true)
            ->whereHas('salaryDetail')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'emp_code', 'first_name', 'last_name']);
    }

    #[Computed]
    public function generatePeriod()
    {
        return $this->generatePeriodId ? PayrollPeriod::find($this->generatePeriodId) : null;
    }

    public function finalize(int $periodId): void
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status !== 'processed') {
            session()->flash('error', 'Only processed periods can be finalized.');
            return;
        }

        $period->update([
            'status'       => 'finalized',
            'finalized_by' => auth()->id(),
        ]);

        ActivityLogService::log('payroll_finalized', "Finalized payroll: {$period->name}", $period);

        $sms = app(SmsNotificationService::class)->payslipNotices($period);

        unset($this->periods);
        session()->flash('success', 'Payroll period finalized. ' . $this->smsSummary($sms));
    }

    /**
     * Text payslip notices for a finalized period. Employees already notified
     * for this period are skipped, so it's safe to press again.
     */
    public function sendPayslipSms(int $periodId): void
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status !== 'finalized') {
            session()->flash('error', 'Payslip notices can only be sent for finalized periods.');
            return;
        }

        $sms = app(SmsNotificationService::class)->payslipNotices($period);
        ActivityLogService::log('payslip_sms', "Sent payslip SMS for {$period->name}: {$sms['queued']} queued", $period);

        session()->flash('success', $this->smsSummary($sms));
    }

    private function smsSummary(array $sms): string
    {
        $text = "Payslip SMS: {$sms['queued']} sent";
        if ($sms['skipped']) {
            $text .= ", {$sms['skipped']} skipped (no valid number)";
        }
        if ($sms['failed']) {
            $text .= ", {$sms['failed']} failed — see SMS Center";
        }
        if ($sms['already']) {
            $text .= ", {$sms['already']} already notified";
        }

        return $text . '.';
    }

    public function openImport(int $periodId): void
    {
        $this->importPeriodId = $periodId;
        $this->importFile     = null;
    }

    public function closeImport(): void
    {
        $this->importPeriodId = null;
        $this->importFile     = null;
    }

    public function importExcel(PayrollWorksheetImportService $importer): void
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls',
        ]);

        $period = PayrollPeriod::findOrFail($this->importPeriodId);

        if ($period->status === 'finalized') {
            session()->flash('error', 'Cannot import into a finalized payroll.');
            return;
        }

        $result = $importer->import($this->importFile->getRealPath(), $period);

        if ($result['matched'] > 0) {
            $period->update(['status' => 'processed']);
        }

        ActivityLogService::log('payroll_imported', "Imported {$result['matched']} payslip(s) from Excel into: {$period->name}", $period);

        $message = "Imported {$result['matched']} payslip(s).";
        if (! empty($result['unmatched'])) {
            $message .= ' Could not match: '.implode(', ', $result['unmatched']).'.';
        }
        session()->flash($result['matched'] > 0 ? 'success' : 'error', $message);

        $this->closeImport();
        unset($this->periods);
    }

    public function exportExcel(int $periodId): mixed
    {
        $period = PayrollPeriod::findOrFail($periodId);

        return Excel::download(
            new PayrollExport($periodId),
            'payroll-' . $period->start_date->format('Y-m-d') . '.xlsx'
        );
    }

    public function viewPayslips(int $periodId): void
    {
        $this->viewPeriodId = $periodId;
    }

    public function closePayslips(): void
    {
        $this->viewPeriodId = null;
    }

    #[Computed]
    public function periodPayslips()
    {
        if (!$this->viewPeriodId) {
            return collect();
        }

        $employees = Employee::with('salaryDetail')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        $payslips = Payslip::where('payroll_period_id', $this->viewPeriodId)
            ->get()
            ->keyBy('employee_id');

        return $employees->map(fn ($emp) => [
            'employee' => $emp,
            'payslip'  => $payslips->get($emp->id),
        ]);
    }

    #[Computed]
    public function viewPeriod()
    {
        return $this->viewPeriodId ? PayrollPeriod::find($this->viewPeriodId) : null;
    }

    #[Computed]
    public function employeeCount(): int
    {
        return Employee::where('is_active', true)->whereHas('salaryDetail')->count();
    }

    public function render()
    {
        return view('livewire.admin.payroll-processor');
    }
}
