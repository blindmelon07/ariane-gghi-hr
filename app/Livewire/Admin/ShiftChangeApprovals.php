<?php

namespace App\Livewire\Admin;

use App\Models\ShiftChangeRequest;
use Illuminate\Database\Eloquent\Model;

class ShiftChangeApprovals extends StepApprovals
{
    protected function model(): string { return ShiftChangeRequest::class; }
    protected function label(): string { return 'shift change'; }
    protected function dateColumn(): string { return 'start_date'; }
    protected function employeeRoute(): string { return 'shift-change.request'; }
    protected function relations(): array { return ['employee', 'schedule']; }

    protected function describe(Model $request): string
    {
        return 'shift change (' . self::dates($request) . ')';
    }

    public function details(Model $request): array
    {
        return [
            'Dates' => self::dates($request),
            'Shift' => $request->schedule
                ? "{$request->schedule->name} · " . self::shiftTimes($request->schedule)
                : '—',
        ];
    }

    protected function finalApprovalError(Model $request): ?string
    {
        if (! $request->schedule?->is_active) {
            return 'The requested shift is no longer active.';
        }

        $overlaps = ShiftChangeRequest::approved()
            ->where('employee_id', $request->employee_id)
            ->whereKeyNot($request->id)
            ->overlapping($request->start_date->toDateString(), $request->end_date->toDateString())
            ->exists();

        return $overlaps ? 'An approved shift change already covers some of these dates.' : null;
    }

    public static function dates(ShiftChangeRequest $request): string
    {
        return $request->start_date->equalTo($request->end_date)
            ? $request->start_date->format('M d, Y')
            : $request->start_date->format('M d') . ' – ' . $request->end_date->format('M d, Y');
    }

    public static function shiftTimes(\App\Models\Schedule $schedule): string
    {
        return \Carbon\Carbon::parse($schedule->time_in)->format('g:i A') . '–' . \Carbon\Carbon::parse($schedule->time_out)->format('g:i A');
    }
}
