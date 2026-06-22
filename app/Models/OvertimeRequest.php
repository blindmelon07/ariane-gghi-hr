<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'requested_hours',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'approved_hours',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date'             => 'date',
            'approved_at'      => 'datetime',
            'requested_hours'  => 'decimal:2',
            'approved_hours'   => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function effectiveHours(): float
    {
        return (float) ($this->approved_hours ?? $this->requested_hours);
    }
}
