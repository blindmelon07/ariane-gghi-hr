<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'Emp Code',
            'Name',
            'Department',
            'Date',
            'Time In',
            'Time Out',
            'Hours Worked',
            'Late (min)',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row['emp_code'],
            $row['name'],
            $row['department'],
            $row['date'],
            $row['time_in'] ?? '',
            $row['time_out'] ?? '',
            $row['hours'],
            $row['late_min'],
            $row['status'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
