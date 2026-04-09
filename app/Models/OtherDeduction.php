<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'deduction_type_id',
        'description',
        'amount_per_cutoff',
        'remaining_balance',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_per_cutoff'  => 'decimal:2',
            'remaining_balance'  => 'decimal:2',
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
}
