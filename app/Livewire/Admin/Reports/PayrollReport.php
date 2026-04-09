<?php

namespace App\Livewire\Admin\Reports;

use App\Exports\PayrollReportExport;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class PayrollReport extends Component
{
    public string $periodId = '';

    public function mount(): void
    {
        $latest = PayrollPeriod::whereIn('status', ['processed', 'finalized'])
            ->orderByDesc('start_date')
            ->first();

        if ($latest) {
            $this->periodId = (string) $latest->id;
        }
    }

    #[Computed]
    public function periods()
    {
        return PayrollPeriod::whereIn('status', ['processed', 'finalized'])
            ->orderByDesc('start_date')
            ->get();
    }

    #[Computed]
    public function payslips()
    {
        if (!$this->periodId) {
            return collect();
        }

        return Payslip::with('employee')
            ->where('payroll_period_id', $this->periodId)
            ->orderBy('employee_id')
            ->get();
    }

    #[Computed]
    public function totals(): array
    {
        $slips = $this->payslips;

        return [
            'basic_pay'       => $slips->sum('basic_pay'),
            'overtime_pay'    => $slips->sum('overtime_pay'),
            'gross_pay'       => $slips->sum('gross_pay'),
            'sss'             => $slips->sum('sss_deduction'),
            'philhealth'      => $slips->sum('philhealth_deduction'),
            'pagibig'         => $slips->sum('pagibig_deduction'),
            'tax'             => $slips->sum('tax_deduction'),
            'other'           => $slips->sum('other_deductions'),
            'total_deductions' => $slips->sum('total_deductions'),
            'net_pay'         => $slips->sum('net_pay'),
        ];
    }

    #[Computed]
    public function selectedPeriod()
    {
        return $this->periodId ? PayrollPeriod::find($this->periodId) : null;
    }

    public function exportExcel()
    {
        $period = $this->selectedPeriod;
        if (!$period) return;

        return Excel::download(
            new PayrollReportExport((int) $this->periodId),
            'payroll-report-' . $period->start_date->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf()
    {
        $period = $this->selectedPeriod;
        if (!$period) return;

        $pdf = Pdf::loadView('pdf.payroll-report', [
            'payslips' => $this->payslips,
            'totals'   => $this->totals,
            'period'   => $period,
        ])->setPaper('A4', 'landscape');

        return response()->streamDownload(fn () => print($pdf->output()),
            'payroll-report-' . $period->start_date->format('Y-m-d') . '.pdf'
        );
    }

    public function render()
    {
        return view('livewire.admin.reports.payroll-report');
    }
}
