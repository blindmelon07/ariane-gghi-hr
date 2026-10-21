@php
    $emp   = $form->employee;
    $fmt   = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format('g:i A') : '';
    $date  = fn ($dt) => $dt?->timezone('Asia/Manila')->format('m/d/Y');
    $final = $form->status === 'approved';
    [$stampText, $stampColor] = match ($form->status) {
        'approved' => ['APPROVED', '#15803d'],
        'pending'  => ['PENDING — ' . (\App\Services\LeaveService::APPROVAL_STEPS[$form->approval_step]['label'] ?? 'for approval'), '#b45309'],
        default    => [strtoupper($form->status), '#b91c1c'],
    };
    $blankRows = max(0, 6 - $form->lines->count());
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Overtime Request — {{ $emp->full_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 13px; }
        .sheet { max-width: 640px; margin: 0 auto; background: #fff; padding: 22px 22px 26px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .head { text-align: center; }
        .head img { height: 44px; }
        h1 { text-align: center; font-size: 19px; margin: 10px 0 2px; letter-spacing: .5px; }
        .requested { text-align: center; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #374151; padding: 4px 6px; vertical-align: top; }
        th { font-weight: bold; text-align: center; }
        .band { background: #38bdf8; color: #0c4a6e; font-weight: bold; text-align: center; font-style: italic; }
        .band.plain { font-style: normal; }
        .num { text-align: center; }
        .purpose { min-height: 70px; white-space: pre-line; }
        .sig-name { display: block; border-bottom: 1px solid #111827; min-height: 18px; text-align: center; font-weight: bold; margin-top: 10px; }
        .sig-cap { display: block; text-align: center; font-size: 11px; }
        .muted { color: #6b7280; font-size: 11px; }
        .stamp { margin: 14px auto 0; text-align: center; font: bold 18px Arial, sans-serif; letter-spacing: 1px;
                 border: 3px solid {{ $stampColor }}; color: {{ $stampColor }}; border-radius: 6px; padding: 6px 10px; max-width: 420px; }
        .foot { text-align: center; font-style: italic; margin-top: 12px; color: #374151; }
        .actions { max-width: 640px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 520px) { body { padding: 6px; font-size: 12px; } .sheet { padding: 14px 10px; } }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; }
            .band { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head"><img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc."></div>
        <h1>OVERTIME REQUEST FORM</h1>
        <div class="requested">Date Requested: <strong>{{ $date($form->created_at) }}</strong></div>

        <table>
            <tr><td>Full Name: <strong>{{ $emp->full_name }}</strong></td><td>Biometrics ID: <strong>{{ $emp->biotime_id ?: $emp->emp_code }}</strong></td></tr>
            <tr><td>Department: <strong>{{ $emp->department ?: '—' }}</strong></td><td>Position: <strong>{{ $emp->position ?: '—' }}</strong></td></tr>
            <tr><td colspan="2" class="band">Note: All overtime requests shall be priorly approved by the Department Head, HR and the Medical Director/CEO.</td></tr>
            <tr><td colspan="2">Regular Working Schedule: From <strong>{{ $fmt($form->schedule_from) ?: '____' }}</strong> To <strong>{{ $fmt($form->schedule_to) ?: '____' }}</strong></td></tr>
            <tr>
                <td>Date/s of Overtime: <strong>{{ $form->lines->map(fn ($l) => $l->date->format('M d'))->join(', ') }}</strong></td>
                <td>For the month of: <strong>{{ $form->monthLabel() }}</strong></td>
            </tr>
        </table>

        <table style="margin-top:-1px">
            <tr><td colspan="4" class="band plain">Propose Overtime Hours per Date</td></tr>
            <tr><th rowspan="2" style="width:34%">Date/s</th><th colspan="2">Overtime Hours</th><th rowspan="2" style="width:22%">Total Hours</th></tr>
            <tr><th>From</th><th>To</th></tr>
            @foreach ($form->lines as $line)
                <tr>
                    <td class="num">{{ $line->date->format('m/d/Y') }}</td>
                    <td class="num">{{ $fmt($line->time_from) }}</td>
                    <td class="num">{{ $fmt($line->time_to) }}</td>
                    <td class="num">{{ number_format((float) $line->requested_hours, 2) }}</td>
                </tr>
            @endforeach
            @for ($i = 0; $i < $blankRows; $i++)
                <tr><td>&nbsp;</td><td></td><td></td><td></td></tr>
            @endfor
            <tr><td colspan="3"><strong>Total Overtime Hours</strong></td><td class="num"><strong>{{ number_format($form->totalHours(), 2) }}</strong></td></tr>
            <tr><td colspan="4"><strong>Purpose and accomplishment:</strong><div class="purpose">{{ $form->purpose }}</div></td></tr>
        </table>

        <table style="margin-top:-1px">
            <tr>
                <td style="width:65%">
                    Requested by:
                    <span class="sig-name">{{ $emp->full_name }}</span>
                    <span class="sig-cap">Signature of the Employee</span>
                </td>
                <td rowspan="2">
                    Verified by:
                    <span class="sig-name">{{ $form->hrVerifier?->name }}</span>
                    <span class="sig-cap"><strong>HR DEPT</strong></span>
                    <div style="margin-top:10px">Date verified: <strong>{{ $date($form->hr_verified_at) ?: '__/__/____' }}</strong></div>
                </td>
            </tr>
            <tr>
                <td>
                    Recommended by:
                    <span class="sig-name">{{ $form->headApprover?->name }}</span>
                    <span class="sig-cap">Department Head</span>
                    @if ($form->head_approved_at)<span class="sig-cap muted">{{ $date($form->head_approved_at) }}</span>@endif
                </td>
            </tr>
            <tr>
                <td>
                    Approved by:
                    <span class="sig-name">{{ $final ? $form->approver?->name : '' }}</span>
                    <span class="sig-cap">CEO/Medical Director</span>
                </td>
                <td>Date approved: <strong>{{ $final ? $date($form->approved_at) : '__/__/____' }}</strong></td>
            </tr>
        </table>

        <div class="stamp">{{ $stampText }}</div>
        @if ($form->status === 'rejected' && $form->remarks)
            <p style="text-align:center;color:#b91c1c">Reason: {{ $form->remarks }}</p>
        @endif
        <div class="foot">OT Form - Revised January 2025</div>
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Back</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>
</html>
