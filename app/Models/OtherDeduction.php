<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OtherDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'description',
        'amount_per_cutoff',
        'cutoff_schedule',
        'starts_on',
        'remaining_balance',
        'auto_stop',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_cutoff'  => 'decimal:2',
            'remaining_balance'  => 'decimal:2',
            'starts_on'          => 'date',
            'auto_stop'          => 'boolean',
            'is_active'          => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function deductionType(): BelongsTo
    {
        return $this->belongsTo(DeductionType::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OtherDeductionPayment::class);
    }

    /**
     * What one cut-off takes: the full amount, except that an auto-stop
     * deduction never takes more than what is left to pay.
     */
    public function amountThisCutoff(): float
    {
        $amount = (float) $this->amount_per_cutoff;

        return $this->auto_stop
            ? round(min($amount, max(0, (float) $this->remaining_balance)), 2)
            : $amount;
    }
}
