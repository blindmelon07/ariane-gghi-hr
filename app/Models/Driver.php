<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Driver extends Model
{
    protected $fillable = [
        'employee_id',
        'license_number',
        'license_expiry',
        'medical_clearance_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry'         => 'date',
            'medical_clearance_date' => 'date',
            'is_active'              => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function tripTickets(): HasMany
    {
        return $this->hasMany(TripTicket::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->employee?->full_name ?? '—';
    }
}
