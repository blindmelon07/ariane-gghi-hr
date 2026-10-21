<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRequest extends Model
{
    protected $fillable = [
        'overtime_form_id',
        'employee_id',
        'date',
        'time_from',
        'time_to',
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

    public function form(): BelongsTo
    {
        return $this->belongsTo(OvertimeForm::class, 'overtime_form_id');
    }

    /** "05:00 PM – 08:00 PM", or null for requests filed before From/To existed. */
    public function timeRangeLabel(): ?string
    {
        if (! $this->time_from || ! $this->time_to) return null;

        return \Carbon\Carbon::parse($this->time_from)->format('h:i A') . ' – ' . \Carbon\Carbon::parse($this->time_to)->format('h:i A');
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
