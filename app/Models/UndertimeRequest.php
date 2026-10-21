<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee's request to leave before the end of their shift.
 * Approval only excuses the undertime on record — payroll still deducts it.
 */
class UndertimeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'date',
        'time_out',
        'reason',
        'status',
        'approval_step',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'approved_at'   => 'datetime',
            'approval_step' => 'integer',
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

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** "05:00 PM"-style label for the planned time-out. */
    public function timeOutLabel(): string
    {
        return \Carbon\Carbon::parse($this->time_out)->format('h:i A');
    }
}
