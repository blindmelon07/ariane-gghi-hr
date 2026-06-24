<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryDetail extends Model
{
    protected $fillable = [
        'employee_id',
        'rate_type',
        'basic_salary',
        'daily_rate',
        'hourly_rate',
        'hazard_pay',
        'rice_allowance',
        'medical_allowance',
        'commodity_allowance',
        'other_allowance',
        'effective_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary'        => 'decimal:2',
            'daily_rate'          => 'decimal:2',
            'hourly_rate'         => 'decimal:2',
            'hazard_pay'          => 'decimal:2',
            'rice_allowance'      => 'decimal:2',
            'medical_allowance'   => 'decimal:2',
            'commodity_allowance' => 'decimal:2',
            'other_allowance'     => 'decimal:2',
            'effective_date'      => 'date',
            'is_active'           => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
