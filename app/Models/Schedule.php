<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    protected $fillable = [
        'name',
        'department',
        'time_in',
        'time_out',
        'break_start',
        'break_end',
        'time_in_2',
        'time_out_2',
        'is_night_shift',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_night_shift' => 'boolean',
            'is_active'      => 'boolean',
        ];
    }

    public function employeeSchedules(): HasMany
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Format the schedule time for display.
     */
    public function getFormattedTimeAttribute(): string
    {
        $time = substr($this->time_in, 0, 5) . ' - ' . substr($this->time_out, 0, 5);

        if ($this->time_in_2 && $this->time_out_2) {
            $time = substr($this->time_in, 0, 5) . '-' . substr($this->time_out, 0, 5)
                  . ', ' . substr($this->time_in_2, 0, 5) . '-' . substr($this->time_out_2, 0, 5);
        }

        return $time;
    }
}
