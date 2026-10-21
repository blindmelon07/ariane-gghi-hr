<?php

namespace App\Livewire\Admin;

use App\Models\GatePass;
use Illuminate\Database\Eloquent\Model;

class GatePassApprovals extends StepApprovals
{
    /** HR approves gate passes on its own — no department head or CEO step. */
    public const STEPS = [
        1 => ['role' => 'hr_admin', 'label' => 'HR'],
    ];

    protected function model(): string { return GatePass::class; }
    protected function label(): string { return 'gate pass'; }
    protected function dateColumn(): string { return 'date'; }
    protected function employeeRoute(): string { return 'gate-pass.request'; }
    protected function steps(): array { return self::STEPS; }

    protected function describe(Model $request): string
    {
        return "gate pass {$request->series_no} ({$request->date->format('M d, Y')}, out at {$request->timeOutLabel()})";
    }

    public function details(Model $request): array
    {
        return [
            'Series No.'   => $request->series_no,
            'Date'         => $request->date->format('D, M d, Y'),
            'Out / Return' => "{$request->timeOutLabel()} – {$request->timeOfArrivalLabel()}",
            'Destination'  => $request->destination,
        ];
    }
}
