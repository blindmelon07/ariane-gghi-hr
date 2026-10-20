<?php

namespace App\Services;

use App\Models\DayOff;
use App\Models\DayOffSwap;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DayOffSwapService
{
    /**
     * A rest day is either an explicit DayOff record or a scheduled rest day
     * (Sunday / weekday_off) that hasn't been swapped away.
     */
    public function isRestDay(Employee $employee, CarbonInterface $day): bool
    {
        return DayOff::where('employee_id', $employee->id)->whereDate('date', $day->toDateString())->exists()
            || app(AttendanceProcessorService::class)->isScheduledRestDay($employee, $day);
    }

    /**
     * The employee's rest days from today through the next $days days,
     * keyed by Y-m-d for use in a select.
     *
     * @return array<string, string>
     */
    public function upcomingRestDays(Employee $employee, int $days = 60): array
    {
        $start = Carbon::now('Asia/Manila')->startOfDay();
        $end   = $start->copy()->addDays($days);

        $dayOffDates = DayOff::where('employee_id', $employee->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        $processor = app(AttendanceProcessorService::class);
        $restDays  = [];

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (in_array($d->toDateString(), $dayOffDates, true) || $processor->isScheduledRestDay($employee, $d)) {
                $restDays[$d->toDateString()] = $d->format('D, M d, Y');
            }
        }

        return $restDays;
    }

    /**
     * Check that the swap still makes sense: the original date is a rest day,
     * the new date isn't already one (or a holiday), and no other pending swap
     * touches either date. Returns [field => message] for the first problem,
     * or null when the swap can go ahead.
     */
    public function validationError(Employee $employee, string $originalDate, string $newDate, ?int $ignoreId = null): ?array
    {
        $original = Carbon::parse($originalDate);
        $new      = Carbon::parse($newDate);

        if (! $this->isRestDay($employee, $original)) {
            return ['originalDate' => 'That date is not one of your rest days.'];
        }

        if ($this->isRestDay($employee, $new)) {
            return ['newDate' => 'You already have a rest day on that date.'];
        }

        if ($this->isHoliday($new)) {
            return ['newDate' => 'That date is a holiday.'];
        }

        $dates = [$original->toDateString(), $new->toDateString()];

        $conflict = DayOffSwap::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($q) use ($dates) {
                foreach ($dates as $date) {
                    $q->orWhereDate('original_date', $date)->orWhereDate('new_date', $date);
                }
            })
            ->exists();

        if ($conflict) {
            return ['originalDate' => 'You already have a pending swap involving one of these dates.'];
        }

        return null;
    }

    /**
     * Move the rest day: the DayOff on the original date (if any) moves to the
     * new date, or a new one is created there when the original was only a
     * scheduled rest day. The approved swap itself marks the original date
     * as a working day (see AttendanceProcessorService::isScheduledRestDay).
     */
    public function apply(DayOffSwap $swap, User $approver): void
    {
        DB::transaction(function () use ($swap, $approver) {
            $note   = 'Swapped from ' . $swap->original_date->format('M d, Y');
            $dayOff = DayOff::where('employee_id', $swap->employee_id)
                ->whereDate('date', $swap->original_date->toDateString())
                ->first();

            if ($dayOff) {
                $dayOff->update([
                    'date'                  => $swap->new_date->toDateString(),
                    'description'           => $note,
                    'is_recurring'          => false,
                    'recurring_day_of_week' => null,
                    'created_by'            => $approver->id,
                ]);
            } else {
                DayOff::create([
                    'employee_id' => $swap->employee_id,
                    'date'        => $swap->new_date->toDateString(),
                    'type'        => 'rest_day',
                    'description' => $note,
                    'created_by'  => $approver->id,
                ]);
            }
        });
    }

    private function isHoliday(CarbonInterface $day): bool
    {
        return Holiday::whereDate('date', $day->toDateString())->exists()
            || Holiday::where('is_recurring', true)
                ->whereMonth('date', $day->month)
                ->whereDay('date', $day->day)
                ->exists();
    }
}
