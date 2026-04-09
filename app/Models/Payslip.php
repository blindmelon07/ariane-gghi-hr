<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payslip extends Model
{
    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'working_days',
        'days_present',
        'days_absent',
        'basic_pay',
        'overtime_hours',
        'overtime_pay',
        'late_minutes',
        'late_deduction',
        'undertime_minutes',
        'undertime_deduction',
        'gross_pay',
        'sss_deduction',
        'philhealth_deduction',
        'pagibig_deduction',
        'tax_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'working_days'         => 'integer',
            'days_present'         => 'decimal:2',
            'days_absent'          => 'decimal:2',
            'basic_pay'            => 'decimal:2',
            'overtime_hours'       => 'decimal:2',
            'overtime_pay'         => 'decimal:2',
            'late_minutes'         => 'integer',
            'late_deduction'       => 'decimal:2',
            'undertime_minutes'    => 'integer',
            'undertime_deduction'  => 'decimal:2',
            'gross_pay'            => 'decimal:2',
            'sss_deduction'        => 'decimal:2',
            'philhealth_deduction' => 'decimal:2',
            'pagibig_deduction'    => 'decimal:2',
            'tax_deduction'        => 'decimal:2',
            'other_deductions'     => 'decimal:2',
            'total_deductions'     => 'decimal:2',
            'net_pay'              => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }
}
