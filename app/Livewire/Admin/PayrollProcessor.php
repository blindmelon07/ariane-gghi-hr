<?php

namespace App\Livewire\Admin;

use App\Exports\PayrollExport;
use App\Jobs\GeneratePayslipsJob;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\ActivityLogService;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

class PayrollProcessor extends Component
{
    use WithPagination;

    public string $filterStatus = 'all';

    // Create period form
    public bool   $showCreate    = false;
    public string $cutoffType    = 'custom';
    public string $startDate     = '';
    public string $endDate       = '';
    public string $periodName    = '';

    // Per-period payslip viewer
    public ?int   $viewPeriodId  = null;

    public function mount(): void
    {
        $this->prefillDates();
    }

    public function prefillDates(): void
    {
        $now = now();

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
            $this->startDate  = '';
            $this->endDate    = '';
            $this->periodName = '';
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
            'periodName' => 'required|string|max:255',
            'cutoffType' => 'required|in:semi_monthly_1,semi_monthly_2,monthly,custom',
            'startDate'  => 'required|date',
            'endDate'    => 'required|date|after_or_equal:startDate',
        ]);

        PayrollPeriod::create([
            'name'        => $this->periodName,
            'cutoff_type' => $this->cutoffType,
            'start_date'  => $this->startDate,
            'end_date'    => $this->endDate,
            'status'      => 'draft',
        ]);

        $this->showCreate = false;
        $this->prefillDates();
        unset($this->periods);
        session()->flash('success', 'Payroll period created.');
    }

    public function generatePayslips(int $periodId): void
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status === 'finalized') {
            session()->flash('error', 'Cannot regenerate a finalized payroll.');
            return;
        }

        $period->update(['status' => 'processing']);
        GeneratePayslipsJob::dispatch($periodId);

        unset($this->periods);
        session()->flash('success', 'Payslip generation queued. Refresh in a moment to see results.');
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

        unset($this->periods);
        session()->flash('success', 'Payroll period finalized.');
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
