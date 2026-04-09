<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LeaveReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        protected Collection $data,
    ) {}

    public function collection(): Collection
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Leave Type',
            'Start Date',
            'End Date',
            'Days',
            'Status',
            'Approved By',
            'Filed On',
        ];
    }

    public function map($row): array
    {
        return [
            $row->employee->full_name ?? '',
            $row->leaveType->name ?? '',
            $row->start_date->format('Y-m-d'),
            $row->end_date->format('Y-m-d'),
            $row->total_days,
            ucfirst($row->status),
            $row->approver?->name ?? '',
            $row->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
