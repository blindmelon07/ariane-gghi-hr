<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Gate pass for patient transport. HR approves before departure; the guard
 * logs departed_at / arrived_at, and the slip is "completed" on arrival.
 */
class PatientTransport extends Model
{
    /** Positions (case-insensitive) allowed to file patient transport slips. */
    public const FILER_POSITIONS = ['institutional worker', 'driver'];

    protected $fillable = [
        'series_no',
        'employee_id',
        'date',
        'patient_name',
        'transported_to',
        'plate_number',
        'driver_name',
        'medical_staff',
        'status',
        'approval_step',
        'approved_by',
        'approved_at',
        'remarks',
        'departed_at',
        'departed_by',
        'arrived_at',
        'arrived_by',
    ];

    protected function casts(): array
    {
        return [
            'date'          => 'date',
            'approved_at'   => 'datetime',
            'departed_at'   => 'datetime',
            'arrived_at'    => 'datetime',
            'approval_step' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Numbered from the row id so two slips filed at once never share a number.
        static::created(function (self $slip) {
            $slip->updateQuietly([
                'series_no' => sprintf('PT-%s-%05d', $slip->date->format('Y'), $slip->id),
            ]);
        });
    }

    public static function canFile(?Employee $employee): bool
    {
        return $employee !== null
            && \in_array(mb_strtolower(trim((string) $employee->position)), self::FILER_POSITIONS, true);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'departed_by');
    }

    public function arrivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'arrived_by');
    }

    /** @return string[] */
    public function medicalStaffList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->medical_staff))));
    }
}
