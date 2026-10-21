<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** What one finalized payroll took from an auto-stop deduction. */
class OtherDeductionPayment extends Model
{
    protected $fillable = [
        'other_deduction_id',
        'payroll_period_id',
        'amount',
        'balance_after',
    ];

    protected function casts(): array
    {
        return [
            'amount'        => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function deduction(): BelongsTo
    {
        return $this->belongsTo(OtherDeduction::class, 'other_deduction_id');
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }
}
