<?php

namespace App\Livewire\Employee;

use App\Models\Employee;
use App\Models\EmployeeSchedule;
use App\Models\OvertimeForm;
use App\Models\OvertimeRequest;
use App\Services\ActivityLogService;
use App\Services\LeaveService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Overtime Request Form (rev. Jan 2025): several dates with From/To times,
 * one purpose, approved Dept Head → HR → CEO / Medical Director.
 */
class OvertimeRequestForm extends Component
{
    /** Rows on the paper form. */
    public const MAX_LINES = 14;

    /** Most overtime hours one date may carry. */
    public const MAX_HOURS_PER_DATE = 8;

    /** @var array<int, array{date: string, from: string, to: string}> */
    public array  $lines   = [];
    public string $purpose = '';

    public function mount(): void
    {
        $this->lines = [$this->blankLine()];
    }

    private function blankLine(): array
    {
        return ['date' => '', 'from' => '', 'to' => ''];
    }

    public function addLine(): void
    {
        if (\count($this->lines) < self::MAX_LINES) {
            $this->lines[] = $this->blankLine();
        }
    }

    public function removeLine(int $index): void
    {
        unset($this->lines[$index]);
        $this->lines = array_values($this->lines) ?: [$this->blankLine()];
    }

    /** Hours between two H:i times; a To earlier than From runs past midnight. */
    public static function hoursBetween(?string $from, ?string $to): float
    {
        if (! $from || ! $to || ! preg_match('/^\d{2}:\d{2}$/', $from) || ! preg_match('/^\d{2}:\d{2}$/', $to)) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i', $from);
        $end   = Carbon::createFromFormat('H:i', $to);
        if ($end->lte($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    #[Computed]
    public function employee(): ?Employee
    {
        return Employee::where('emp_code', auth()->user()->employee_code)->first();
    }

    /** Regular working schedule today: the assigned shift, or the 8:00–5:00 default. */
    #[Computed]
    public function regularSchedule(): array
    {
        $today      = now()->toDateString();
        $assignment = $this->employee
            ? EmployeeSchedule::with('schedule')
                ->where('employee_id', $this->employee->id)
                ->whereDate('effective_from', '<=', $today)
                ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $today))
                ->orderByDesc('effective_from')
                ->first()
            : null;

        return [
            'from' => $assignment?->schedule ? substr($assignment->schedule->time_in, 0, 5) : '08:00',
            'to'   => $assignment?->schedule ? substr($assignment->schedule->time_out, 0, 5) : '17:00',
        ];
    }

    #[Computed]
    public function lineHours(): array
    {
        return array_map(fn ($l) => self::hoursBetween($l['from'] ?? null, $l['to'] ?? null), $this->lines);
    }

    #[Computed]
    public function totalHours(): float
    {
        return round(array_sum($this->lineHours), 2);
    }

    #[Computed]
    public function myForms()
    {
        if (! $this->employee) {
            return collect();
        }

        return OvertimeForm::with('lines')
            ->where('employee_id', $this->employee->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public function updatedLines(): void
    {
        unset($this->lineHours, $this->totalHours);
    }

    public function submit(): void
    {
        $this->validate([
            'lines'        => 'required|array|min:1|max:' . self::MAX_LINES,
            'lines.*.date' => 'required|date|after_or_equal:today|distinct',
            'lines.*.from' => 'required|date_format:H:i',
            'lines.*.to'   => 'required|date_format:H:i|different:lines.*.from',
            'purpose'      => 'required|string|min:5|max:2000',
        ], [
            'lines.*.date.after_or_equal' => 'Overtime must be requested before the date.',
            'lines.*.date.distinct'       => 'Each date can only be listed once.',
            'lines.*.to.different'        => 'From and To cannot be the same time.',
        ], [
            'lines.*.date' => 'date',
            'lines.*.from' => 'from',
            'lines.*.to'   => 'to',
        ]);

        $employee = $this->employee;
        if (! $employee) {
            $this->addError('general', 'Employee record not found.');
            return;
        }

        $months = collect($this->lines)->map(fn ($l) => substr($l['date'], 0, 7))->unique();
        if ($months->count() > 1) {
            $this->addError('general', 'All dates on one form must be in the same month. File a separate form for the other month.');
            return;
        }

        foreach ($this->lines as $i => $line) {
            if ($this->lineHours[$i] > self::MAX_HOURS_PER_DATE) {
                $this->addError("lines.{$i}.to", 'At most ' . self::MAX_HOURS_PER_DATE . ' overtime hours per date.');
                return;
            }
        }

        $taken = OvertimeRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'approved'])
            ->where(function ($q) {
                foreach (array_column($this->lines, 'date') as $date) {
                    $q->orWhereDate('date', $date);
                }
            })
            ->pluck('date')
            ->map(fn ($d) => $d->toDateString())
            ->all();

        foreach ($this->lines as $i => $line) {
            if (\in_array($line['date'], $taken, true)) {
                $this->addError("lines.{$i}.date", 'You already have a pending or approved overtime request for this date.');
                return;
            }
        }

        $form = DB::transaction(function () use ($employee) {
            $form = OvertimeForm::create([
                'employee_id'   => $employee->id,
                'purpose'       => $this->purpose,
                'schedule_from' => $this->regularSchedule['from'],
                'schedule_to'   => $this->regularSchedule['to'],
                'status'        => 'pending',
                'approval_step' => app(LeaveService::class)->getInitialStep(auth()->user()),
            ]);

            foreach ($this->lines as $i => $line) {
                $form->lines()->create([
                    'employee_id'     => $employee->id,
                    'date'            => $line['date'],
                    'time_from'       => $line['from'],
                    'time_to'         => $line['to'],
                    'requested_hours' => $this->lineHours[$i],
                    'reason'          => $this->purpose,
                    'status'          => 'pending',
                ]);
            }

            return $form;
        });

        ActivityLogService::log('overtime_filed', "Overtime request filed by {$employee->full_name}: " . \count($this->lines) . " date(s), {$this->totalHours} hrs", $employee);

        $this->lines   = [$this->blankLine()];
        $this->purpose = '';
        unset($this->myForms, $this->lineHours, $this->totalHours);

        session()->flash('success', 'Overtime request submitted. It goes to your Department Head, then HR, then the CEO / Medical Director.');
    }

    public function cancel(int $id): void
    {
        $form = OvertimeForm::where('employee_id', $this->employee?->id)->find($id);

        if ($form && $form->status === 'pending') {
            $form->update(['status' => 'cancelled', 'approval_step' => null]);
            unset($this->myForms);
            session()->flash('success', 'Overtime request cancelled.');
        }
    }

    public function render()
    {
        return view('livewire.employee.overtime-request-form');
    }
}
