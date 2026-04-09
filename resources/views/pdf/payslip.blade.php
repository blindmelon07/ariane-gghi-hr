<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip - {{ $payslip->employee->full_name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; }
        .container { padding: 30px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #4f46e5; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #4f46e5; margin-bottom: 4px; }
        .header p { font-size: 10px; color: #666; }
        .meta { display: table; width: 100%; margin-bottom: 20px; }
        .meta-left, .meta-right { display: table-cell; width: 50%; vertical-align: top; }
        .meta-right { text-align: right; }
        .meta p { margin-bottom: 3px; }
        .meta strong { color: #111; }
        .section-title { background: #f3f4f6; padding: 6px 10px; font-weight: bold; font-size: 11px; text-transform: uppercase; color: #4f46e5; margin-bottom: 8px; }
        .two-col { display: table; width: 100%; margin-bottom: 15px; }
        .col { display: table-cell; width: 48%; vertical-align: top; }
        .col:first-child { padding-right: 15px; }
        .col:last-child { padding-left: 15px; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 4px 8px; }
        table td:last-child { text-align: right; font-family: monospace; }
        table tr.total { border-top: 1.5px solid #333; font-weight: bold; }
        table tr.total td { padding-top: 8px; }
        .net-pay { text-align: center; margin: 20px 0; padding: 15px; background: #eef2ff; border: 1px solid #c7d2fe; }
        .net-pay .amount { font-size: 24px; font-weight: bold; color: #4f46e5; }
        .net-pay .label { font-size: 10px; text-transform: uppercase; color: #666; letter-spacing: 1px; }
        .attendance { margin-bottom: 15px; }
        .attendance td { text-align: center; }
        .attendance td:first-child { text-align: left; }
        .signatures { display: table; width: 100%; margin-top: 50px; }
        .sig-box { display: table-cell; width: 33%; text-align: center; }
        .sig-line { border-top: 1px solid #999; width: 140px; margin: 0 auto; padding-top: 4px; font-size: 10px; color: #666; }
        .footer { text-align: center; margin-top: 30px; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        {{-- Header --}}
        <div class="header">
            <h1>{{ config('company.name', 'Company Name') }}</h1>
            <p>{{ config('company.address', '') }}</p>
            @if (config('company.tin'))
                <p>TIN: {{ config('company.tin') }}</p>
            @endif
            <p style="margin-top: 8px; font-size: 13px; font-weight: bold; color: #333;">PAYSLIP</p>
        </div>

        {{-- Employee & Period Info --}}
        <div class="meta">
            <div class="meta-left">
                <p><strong>Employee:</strong> {{ $payslip->employee->full_name }}</p>
                <p><strong>Code:</strong> {{ $payslip->employee->emp_code }}</p>
                <p><strong>Department:</strong> {{ $payslip->employee->department }}</p>
                <p><strong>Position:</strong> {{ $payslip->employee->position }}</p>
            </div>
            <div class="meta-right">
                <p><strong>Period:</strong> {{ $payslip->payrollPeriod->name }}</p>
                <p><strong>Dates:</strong> {{ $payslip->payrollPeriod->start_date->format('M d') }} – {{ $payslip->payrollPeriod->end_date->format('M d, Y') }}</p>
                <p><strong>Date Generated:</strong> {{ $payslip->created_at->format('M d, Y') }}</p>
            </div>
        </div>

        {{-- Attendance Summary --}}
        <div class="section-title">Attendance Summary</div>
        <table class="attendance" style="margin-bottom: 15px;">
            <tr>
                <td><strong>Working Days:</strong> {{ $payslip->working_days }}</td>
                <td><strong>Days Present:</strong> {{ $payslip->days_present }}</td>
                <td><strong>Days Absent:</strong> {{ $payslip->days_absent }}</td>
                <td><strong>OT Hours:</strong> {{ $payslip->overtime_hours }}</td>
            </tr>
        </table>

        {{-- Two-column Earnings & Deductions --}}
        <div class="two-col">
            <div class="col">
                <div class="section-title">Earnings</div>
                <table>
                    <tr><td>Basic Pay ({{ $payslip->days_present }} days)</td><td>{{ number_format($payslip->basic_pay, 2) }}</td></tr>
                    <tr><td>Overtime Pay ({{ $payslip->overtime_hours }} hrs)</td><td>{{ number_format($payslip->overtime_pay, 2) }}</td></tr>
                    @if ($payslip->late_deduction > 0)
                        <tr><td>Late Deduction ({{ $payslip->late_minutes }} min)</td><td>({{ number_format($payslip->late_deduction, 2) }})</td></tr>
                    @endif
                    @if ($payslip->undertime_deduction > 0)
                        <tr><td>Undertime Deduction ({{ $payslip->undertime_minutes }} min)</td><td>({{ number_format($payslip->undertime_deduction, 2) }})</td></tr>
                    @endif
                    <tr class="total"><td>Gross Pay</td><td>{{ number_format($payslip->gross_pay, 2) }}</td></tr>
                </table>
            </div>
            <div class="col">
                <div class="section-title">Deductions</div>
                <table>
                    <tr><td>SSS</td><td>{{ number_format($payslip->sss_deduction, 2) }}</td></tr>
                    <tr><td>PhilHealth</td><td>{{ number_format($payslip->philhealth_deduction, 2) }}</td></tr>
                    <tr><td>Pag-IBIG</td><td>{{ number_format($payslip->pagibig_deduction, 2) }}</td></tr>
                    <tr><td>Withholding Tax</td><td>{{ number_format($payslip->tax_deduction, 2) }}</td></tr>
                    @if ($payslip->other_deductions > 0)
                        <tr><td>Other Deductions</td><td>{{ number_format($payslip->other_deductions, 2) }}</td></tr>
                    @endif
                    <tr class="total"><td>Total Deductions</td><td>{{ number_format($payslip->total_deductions, 2) }}</td></tr>
                </table>
            </div>
        </div>

        {{-- Net Pay --}}
        <div class="net-pay">
            <div class="label">Net Pay</div>
            <div class="amount">₱ {{ number_format($payslip->net_pay, 2) }}</div>
        </div>

        {{-- Signatures --}}
        <div class="signatures">
            <div class="sig-box">
                <div class="sig-line">Prepared By</div>
            </div>
            <div class="sig-box">
                <div class="sig-line">Checked By</div>
            </div>
            <div class="sig-box">
                <div class="sig-line">Received By</div>
            </div>
        </div>

        <div class="footer">
            This is a system-generated payslip. For questions, contact the HR department.
        </div>
    </div>
</body>
</html>
