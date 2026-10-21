<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryDeductionLine extends Model
{
    public const CUTOFF_LABELS = [
        'both' => 'Every cut-off',
        '1st'  => '1st cut-off (1–15)',
        '2nd'  => '2nd cut-off (16–end)',
    ];

    protected $fillable = [
        'salary_deduction_request_id',
        'particular',
        'description',
        'amount_per_cutoff',
        'total_amount',
        'cutoff_schedule',
        'starts_on',
        'other_deduction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_cutoff' => 'decimal:2',
            'total_amount'      => 'decimal:2',
            'starts_on'         => 'date',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(SalaryDeductionRequest::class, 'salary_deduction_request_id');
    }

    public function deduction(): BelongsTo
    {
        return $this->belongsTo(OtherDeduction::class, 'other_deduction_id');
    }

    public function label(): string
    {
        $base = SalaryDeductionRequest::PARTICULARS[$this->particular][0] ?? $this->particular;

        return $this->description ? "{$base}: {$this->description}" : $base;
    }

    public function periodLabel(): string
    {
        return (self::CUTOFF_LABELS[$this->cutoff_schedule] ?? $this->cutoff_schedule) . ', from ' . $this->starts_on->format('M d, Y');
    }
}
