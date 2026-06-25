<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TripTicket extends Model
{
    protected $fillable = [
        'employee_id',
        'vehicle_id',
        'driver_id',
        'department',
        'destination_from',
        'destination_to',
        'departure_datetime',
        'return_datetime',
        'passengers',
        'purpose',
        'status',
        'approval_step',
        'approved_by',
        'approved_at',
        'departed_at',
        'departed_by',
        'completed_at',
        'completed_by',
        'return_remarks',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'departure_datetime' => 'datetime',
            'return_datetime'    => 'datetime',
            'approval_step'      => 'integer',
            'approved_at'        => 'datetime',
            'departed_at'        => 'datetime',
            'completed_at'       => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function departedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'departed_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(TripTicketApproval::class)->orderBy('step');
    }
}
