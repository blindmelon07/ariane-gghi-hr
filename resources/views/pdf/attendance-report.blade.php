<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #333; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 14px; margin: 0; }
        .header p { color: #666; margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; }
        th { background: #f3f4f6; font-weight: bold; text-transform: uppercase; font-size: 8px; }
        td { font-size: 9px; }
        .text-right { text-align: right; }
        .late { color: #dc2626; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('company.name', 'Company') }} — Attendance Report</h1>
        <p>Period: {{ $dateFrom }} to {{ $dateTo }}</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Code</th><th>Name</th><th>Dept</th><th>Date</th>
                <th>Time In</th><th>Time Out</th><th class="text-right">Hours</th>
                <th class="text-right">Late</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>{{ $row['emp_code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['department'] }}</td>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['time_in'] ?? '—' }}</td>
                    <td>{{ $row['time_out'] ?? '—' }}</td>
                    <td class="text-right">{{ number_format($row['hours'], 2) }}</td>
                    <td class="text-right {{ $row['late_min'] > 0 ? 'late' : '' }}">{{ $row['late_min'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
