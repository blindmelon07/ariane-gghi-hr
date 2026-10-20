<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonInterface;

/**
 * An employee's request to move a rest day from original_date to new_date.
 * Once approved, original_date counts as a working day even if it falls on
 * the employee's scheduled rest day (Sunday / weekday_off), and new_date
 * carries a DayOff record.
 */
class DayOffSwap extends Model
{
    protected $fillable = [
        'employee_id',
        'original_date',
        'new_date',
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
            'original_date' => 'date',
            'new_date'      => 'date',
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

    /** Whether $date was a rest day the employee swapped away, making it a working day. */
    public static function isSwappedWorkday(int $employeeId, CarbonInterface|string $date): bool
    {
        return static::approved()
            ->where('employee_id', $employeeId)
            ->whereDate('original_date', $date instanceof CarbonInterface ? $date->toDateString() : $date)
            ->exists();
    }

    /**
     * Swapped-away rest days (Y-m-d) between $from and $to, inclusive.
     *
     * @return string[]
     */
    public static function swappedWorkdays(int $employeeId, CarbonInterface|string $from, CarbonInterface|string $to): array
    {
        return static::approved()
            ->where('employee_id', $employeeId)
            ->whereDate('original_date', '>=', $from instanceof CarbonInterface ? $from->toDateString() : $from)
            ->whereDate('original_date', '<=', $to instanceof CarbonInterface ? $to->toDateString() : $to)
            ->pluck('original_date')
            ->map(fn ($d) => $d->toDateString())
            ->all();
    }
}
