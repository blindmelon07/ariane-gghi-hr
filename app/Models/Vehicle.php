<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'plate_number',
        'make',
        'model',
        'vehicle_type',
        'capacity',
        'year',
        'status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity'  => 'integer',
            'year'      => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->make} {$this->model} ({$this->plate_number})";
    }

    public function tripTickets(): HasMany
    {
        return $this->hasMany(TripTicket::class);
    }
}
