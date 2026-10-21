<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Permission for an employee to leave the premises during work hours.
 * Approved by HR; the employee shows the approved pass to the guard.
 */
class GatePass extends Model
{
    protected $fillable = [
        'series_no',
        'employee_id',
        'date',
        'time_out',
        'time_of_arrival',
        'destination',
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

    protected static function booted(): void
    {
        // Numbered from the row id so two passes filed at once never share a number.
        static::created(function (self $pass) {
            $pass->updateQuietly([
                'series_no' => sprintf('GP-%s-%05d', $pass->date->format('Y'), $pass->id),
            ]);
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function timeOutLabel(): string
    {
        return Carbon::parse($this->time_out)->format('h:i A');
    }

    public function timeOfArrivalLabel(): string
    {
        return Carbon::parse($this->time_of_arrival)->format('h:i A');
    }
}
