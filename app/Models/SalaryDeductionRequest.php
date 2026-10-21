<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Salary Deduction Form. HR approves; each line then becomes an active
 * OtherDeduction (see SalaryDeductionService::apply).
 */
class SalaryDeductionRequest extends Model
{
    /**
     * Particulars on the paper form.
     * key => [label, deduction type code, is a loan (needs total + amortization table), needs a description]
     */
    public const PARTICULARS = [
        'gsac_savings' => ['GSAC Savings', 'GSAC_SAVINGS', false, false],
        'gsac_loans'   => ['GSAC Loans', 'GSAC_LOAN', true, false],
        'gsac_share'   => ['GSAC Share Capital', 'GSAC_SHARE', false, false],
        'gsac_damayan' => ['GSAC Damayan Contribution', 'GSAC_DAMAYAN', false, false],
        'sss'          => ['SSS Loan', 'SSS_LOAN', true, false],
        'pagibig'      => ['Pag-IBIG Loan', 'PAGIBIG_LOAN', true, false],
        'other_loan'   => ['Other Loan', 'OTHER_LOAN', true, true],
        'cash_advance' => ['Cash Advance', 'CASH_ADVANCE', true, false],
        'others'       => ['Others', 'OTHERS', false, true],
    ];

    /** Loans that come with an amortization table from a bank or agency. */
    public const NEEDS_AMORTIZATION = ['gsac_loans', 'sss', 'pagibig', 'other_loan'];

    protected $fillable = [
        'employee_id',
        'attachment_path',
        'status',
        'approval_step',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'approval_step' => 'integer',
            'approved_at'   => 'datetime',
        ];
    }

    /** Summary shown where other requests show a reason. */
    protected function reason(): Attribute
    {
        return Attribute::get(fn () => $this->lines->map(fn ($l) => $l->label())->join(', '));
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalaryDeductionLine::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function totalPerCutoff(): float
    {
        return round((float) $this->lines->sum('amount_per_cutoff'), 2);
    }
}
