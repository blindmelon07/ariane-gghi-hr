<?php

namespace App\Livewire\Admin;

use App\Models\UndertimeRequest;
use Illuminate\Database\Eloquent\Model;

class UndertimeApprovals extends StepApprovals
{
    protected function model(): string { return UndertimeRequest::class; }
    protected function label(): string { return 'undertime'; }
    protected function dateColumn(): string { return 'date'; }
    protected function employeeRoute(): string { return 'undertime.request'; }

    protected function describe(Model $request): string
    {
        return "undertime request ({$request->date->format('M d, Y')}, out at {$request->timeOutLabel()})";
    }

    public function details(Model $request): array
    {
        return [
            'Date'     => $request->date->format('D, M d, Y'),
            'Time Out' => $request->timeOutLabel(),
        ];
    }
}
