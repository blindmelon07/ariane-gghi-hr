<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * One Overtime Request Form: several dates (OvertimeRequest lines), one
 * purpose, approved Dept Head → HR → CEO/MD. The lines mirror the form's
 * status so payroll, which reads approved lines, needs no change.
 */
class OvertimeForm extends Model
{
    protected $fillable = [
        'employee_id',
        'purpose',
        'schedule_from',
        'schedule_to',
        'status',
        'approval_step',
        'head_approved_by',
        'head_approved_at',
        'hr_verified_by',
        'hr_verified_at',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'approval_step'    => 'integer',
            'head_approved_at' => 'datetime',
            'hr_verified_at'   => 'datetime',
            'approved_at'      => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updated(function (self $form) {
            if (! $form->wasChanged('status')) {
                return;
            }

            $form->lines()->update([
                'status'         => $form->status,
                'approved_by'    => $form->status === 'pending' ? null : $form->approved_by,
                'approved_at'    => $form->status === 'pending' ? null : $form->approved_at,
                'approved_hours' => $form->status === 'approved' ? DB::raw('requested_hours') : null,
                'remarks'        => $form->remarks,
            ]);
        });
    }

    /** Lets shared request lists show the purpose where other requests show a reason. */
    protected function reason(): Attribute
    {
        return Attribute::get(fn () => $this->purpose);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class)->orderBy('date')->orderBy('time_from');
    }

    public function headApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_approved_by');
    }

    public function hrVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_verified_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function totalHours(): float
    {
        return round((float) $this->lines->sum('requested_hours'), 2);
    }

    /** "October 2026", or "October – November 2026" if the dates span months. */
    public function monthLabel(): string
    {
        $first = $this->lines->min('date');
        $last  = $this->lines->max('date');
        if (! $first) return '—';

        return $first->format('Y-m') === $last->format('Y-m')
            ? $first->format('F Y')
            : $first->format('F') . ' – ' . $last->format('F Y');
    }

    public function scheduleLabel(): string
    {
        if (! $this->schedule_from || ! $this->schedule_to) return '—';

        return Carbon::parse($this->schedule_from)->format('g:i A') . ' to ' . Carbon::parse($this->schedule_to)->format('g:i A');
    }
}
