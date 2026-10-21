@php
    $valid   = $pass->status === 'approved';
    $isToday = $pass->date->isSameDay(now('Asia/Manila'));
    [$stampText, $stampColor] = match ($pass->status) {
        'approved' => ['APPROVED', '#15803d'],
        'pending'  => ['PENDING — NOT YET VALID', '#b45309'],
        default    => [strtoupper($pass->status) . ' — NOT VALID', '#b91c1c'],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gate Pass {{ $pass->series_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: Georgia, 'Times New Roman', serif; color: #111827; }
        .sheet { max-width: 560px; margin: 0 auto; background: #fff; padding: 24px 22px 28px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); position: relative; }
        .head { display: flex; align-items: center; gap: 12px; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .head img { height: 46px; }
        .head .addr { margin-left: auto; text-align: right; font: 10px/1.35 Arial, sans-serif; color: #374151; }
        h1 { text-align: center; font-size: 20px; letter-spacing: .5px; text-decoration: underline; margin: 18px 0 14px; }
        .row { display: flex; gap: 8px; margin: 9px 0; font-size: 15px; }
        .row .label { width: 150px; flex-shrink: 0; font-weight: bold; }
        .row .value { flex: 1; border-bottom: 1px solid #6b7280; padding: 0 4px 2px; font-family: Arial, sans-serif; }
        .stamp { margin: 18px auto 6px; text-align: center; font: bold 22px Arial, sans-serif; letter-spacing: 1px;
                 border: 3px solid {{ $stampColor }}; color: {{ $stampColor }}; border-radius: 6px; padding: 8px 10px; transform: rotate(-2deg); max-width: 380px; }
        .note { text-align: center; font: 13px Arial, sans-serif; color: #b45309; margin: 4px 0 0; }
        .sign { margin-top: 26px; text-align: center; font-size: 15px; }
        .sign .name { display: inline-block; min-width: 260px; border-bottom: 1px solid #111827; padding: 18px 8px 2px; font-family: Arial, sans-serif; }
        .sign .caption { margin-top: 4px; }
        .meta { font: 11px Arial, sans-serif; color: #6b7280; margin-top: 2px; }
        .actions { max-width: 560px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; font-family: Arial, sans-serif; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 480px) {
            body { padding: 8px; }
            .sheet { padding: 18px 14px 22px; }
            .head .addr { display: none; }
            .row { flex-direction: column; gap: 2px; }
            .row .label { width: auto; font-size: 13px; }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head">
            <img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc.">
            <div class="addr">{{ config('company.address', '') }}</div>
        </div>

        <h1>GATE PASS</h1>

        <div class="row"><span class="label">Series No.</span><span class="value">{{ $pass->series_no }}</span></div>
        <div class="row"><span class="label">Date</span><span class="value">{{ $pass->date->format('F d, Y (l)') }}</span></div>
        <div class="row"><span class="label">Time</span><span class="value">{{ $pass->timeOutLabel() }}</span></div>
        <div class="row"><span class="label">Name</span><span class="value">{{ $pass->employee->full_name }}</span></div>
        <div class="row"><span class="label">Position</span><span class="value">{{ $pass->employee->position ?: '—' }}</span></div>
        <div class="row"><span class="label">Department</span><span class="value">{{ $pass->employee->department ?: '—' }}</span></div>
        <div class="row"><span class="label">Reason/s</span><span class="value">{{ $pass->reason }}</span></div>
        <div class="row"><span class="label">Destination</span><span class="value">{{ $pass->destination }}</span></div>
        <div class="row"><span class="label">Time of Arrival</span><span class="value">{{ $pass->timeOfArrivalLabel() }}</span></div>

        <div class="stamp">{{ $stampText }}</div>
        @if ($valid && ! $isToday)
            <p class="note">Valid only on {{ $pass->date->format('F d, Y') }}.</p>
        @endif
        @if ($pass->status === 'rejected' && $pass->remarks)
            <p class="note" style="color:#b91c1c">Reason: {{ $pass->remarks }}</p>
        @endif

        <div class="sign">
            <div>Signed by:</div>
            <div class="name">{{ $pass->employee->full_name }}</div>
            <div class="caption">Signature over Printed Name</div>
        </div>

        <div class="sign">
            <div>Approved by:</div>
            <div class="name">{{ $valid ? ($pass->approver?->name ?? '—') : '' }}</div>
            <div class="caption">HR</div>
            @if ($valid && $pass->approved_at)
                <div class="meta">Approved {{ $pass->approved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</div>
            @endif
        </div>
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('gate-pass.request') }}">Back</a>
        @if ($valid)
            <button type="button" onclick="window.print()">Print</button>
        @endif
    </div>
</body>
</html>
