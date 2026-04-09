<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payroll Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #1f2937; }
        h2 { text-align: center; margin-bottom: 2px; }
        .sub { text-align: center; color: #6b7280; margin-bottom: 12px; font-size: 9px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; text-align: left; padding: 4px 6px; border-bottom: 2px solid #d1d5db; font-size: 9px; text-transform: uppercase; }
        td { padding: 3px 6px; border-bottom: 1px solid #e5e7eb; }
        .r { text-align: right; }
        .b { font-weight: bold; }
        .red { color: #dc2626; }
        .green { color: #16a34a; }
        tfoot td { background: #f3f4f6; font-weight: bold; border-top: 2px solid #d1d5db; }
    </style>
</head>
<body>
    <div style="text-align:center; margin-bottom:4px;">
        <img src="{{ public_path('images/gghi logo.png') }}" alt="GSAC General Hospital Inc." style="height:50px; width:auto;" />
    </div>
    <p class="sub">
        Payroll Report: {{ $period->start_date->format('M d') }} &ndash; {{ $period->end_date->format('M d, Y') }}
        | Status: {{ ucfirst($period->status) }}
    </p>

    <table>
        <thead>
            <tr>
                <th>Emp Code</th>
                <th>Name</th>
                <th class="r">Days</th>
                <th class="r">Basic</th>
                <th class="r">OT</th>
                <th class="r">Gross</th>
                <th class="r">SSS</th>
                <th class="r">PH</th>
                <th class="r">PI</th>
                <th class="r">Tax</th>
                <th class="r">Others</th>
                <th class="r">Deductions</th>
                <th class="r">Net Pay</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payslips as $slip)
            <tr>
                <td>{{ $slip->employee->emp_code ?? '' }}</td>
                <td>{{ $slip->employee->full_name ?? '' }}</td>
                <td class="r">{{ number_format($slip->days_present, 1) }}</td>
                <td class="r">{{ number_format($slip->basic_pay, 2) }}</td>
                <td class="r">{{ number_format($slip->overtime_pay, 2) }}</td>
                <td class="r b">{{ number_format($slip->gross_pay, 2) }}</td>
                <td class="r red">{{ number_format($slip->sss_deduction, 2) }}</td>
                <td class="r red">{{ number_format($slip->philhealth_deduction, 2) }}</td>
                <td class="r red">{{ number_format($slip->pagibig_deduction, 2) }}</td>
                <td class="r red">{{ number_format($slip->tax_deduction, 2) }}</td>
                <td class="r red">{{ number_format($slip->other_deductions, 2) }}</td>
                <td class="r red b">{{ number_format($slip->total_deductions, 2) }}</td>
                <td class="r green b">{{ number_format($slip->net_pay, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="b">TOTALS</td>
                <td class="r"></td>
                <td class="r">{{ number_format($totals['basic_pay'], 2) }}</td>
                <td class="r">{{ number_format($totals['overtime_pay'], 2) }}</td>
                <td class="r">{{ number_format($totals['gross_pay'], 2) }}</td>
                <td class="r red">{{ number_format($totals['sss'], 2) }}</td>
                <td class="r red">{{ number_format($totals['philhealth'], 2) }}</td>
                <td class="r red">{{ number_format($totals['pagibig'], 2) }}</td>
                <td class="r red">{{ number_format($totals['tax'], 2) }}</td>
                <td class="r red">{{ number_format($totals['other'], 2) }}</td>
                <td class="r red">{{ number_format($totals['total_deductions'], 2) }}</td>
                <td class="r green">{{ number_format($totals['net_pay'], 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
