<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = [
        'date',
        'name',
        'type',
        'is_recurring',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date'         => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'regular'             => 'Regular Holiday',
            'special_non_working' => 'Special Non-Working',
            'special_working'     => 'Special Working',
            default               => $this->type,
        };
    }
}
