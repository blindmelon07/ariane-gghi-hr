<?php

namespace App\Livewire\Admin;

use App\Exports\PayrollExport;
use App\Jobs\GeneratePayslipsJob;
use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\ActivityLogService;
use App\Models\Payslip;
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
    public string $cutoffType    = 'semi_monthly_1';
    public string $startDate     = '';
    public string $endDate       = '';
    public string $periodName    = '';

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
            'cutoffType' => 'required|in:semi_monthly_1,semi_monthly_2,monthly',
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
