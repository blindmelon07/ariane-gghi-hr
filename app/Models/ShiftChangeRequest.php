<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee's request to work a different shift on specific dates.
 * Once approved, attendance measures late/undertime against this shift
 * for start_date..end_date instead of the employee's assigned schedule.
 */
class ShiftChangeRequest extends Model
{
    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'schedule_id',
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
            'start_date'    => 'date',
            'end_date'      => 'date',
            'approved_at'   => 'datetime',
            'approval_step' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /** Requests that touch $from..$to (pending or approved), for overlap checks. */
    public function scopeOverlapping(Builder $query, string $from, string $to): Builder
    {
        return $query->whereIn('status', ['pending', 'approved'])
            ->whereDate('start_date', '<=', $to)
            ->whereDate('end_date', '>=', $from);
    }
}
