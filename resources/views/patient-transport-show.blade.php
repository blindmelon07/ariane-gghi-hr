@php
    $valid = in_array($slip->status, ['approved', 'completed'], true);
    [$stampText, $stampColor] = match ($slip->status) {
        'approved'  => ['APPROVED', '#15803d'],
        'completed' => ['APPROVED · TRIP COMPLETED', '#15803d'],
        'pending'   => ['PENDING — NOT YET VALID', '#b45309'],
        default     => [strtoupper($slip->status) . ' — NOT VALID', '#b91c1c'],
    };
    $time = fn ($dt) => $dt?->timezone('Asia/Manila')->format('h:i A');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Transport {{ $slip->series_no }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: Georgia, 'Times New Roman', serif; color: #111827; }
        .sheet { max-width: 560px; margin: 0 auto; background: #fff; padding: 24px 22px 28px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .head { text-align: center; border-bottom: 2px solid #111827; padding-bottom: 10px; }
        .head img { height: 46px; }
        h1 { text-align: center; font-size: 18px; letter-spacing: .3px; margin: 16px 0 14px; }
        .row { display: flex; gap: 8px; margin: 9px 0; font-size: 15px; }
        .row .label { width: 170px; flex-shrink: 0; font-weight: bold; }
        .row .value { flex: 1; border-bottom: 1px solid #6b7280; padding: 0 4px 2px; font-family: Arial, sans-serif; }
        .pair { display: flex; gap: 16px; }
        .pair .row { flex: 1; }
        .pair .row .label { width: auto; }
        .staff { margin: 9px 0; font-size: 15px; }
        .staff .label { font-weight: bold; }
        .staff ol { margin: 6px 0 0 28px; padding: 0; font-family: Arial, sans-serif; }
        .staff li { border-bottom: 1px solid #6b7280; padding: 2px 4px; margin-bottom: 4px; }
        .stamp { margin: 18px auto 6px; text-align: center; font: bold 20px Arial, sans-serif; letter-spacing: 1px;
                 border: 3px solid {{ $stampColor }}; color: {{ $stampColor }}; border-radius: 6px; padding: 8px 10px; transform: rotate(-2deg); max-width: 400px; }
        .sign { margin-top: 24px; text-align: center; font-size: 15px; }
        .sign .name { display: inline-block; min-width: 260px; border-bottom: 1px solid #111827; padding: 18px 8px 2px; font-family: Arial, sans-serif; }
        .meta { font: 11px Arial, sans-serif; color: #6b7280; margin-top: 2px; }
        .foot { margin-top: 18px; font: italic 12px Georgia, serif; }
        .actions { max-width: 560px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; font-family: Arial, sans-serif; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 480px) {
            body { padding: 8px; }
            .sheet { padding: 18px 14px 22px; }
            .row, .pair { flex-direction: column; gap: 2px; }
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
        </div>

        <h1>GATE PASS FOR PATIENT TRANSPORT SLIP</h1>

        <div class="row"><span class="label">Series No.</span><span class="value">{{ $slip->series_no }}</span></div>
        <div class="row"><span class="label">Date</span><span class="value">{{ $slip->date->format('F d, Y (l)') }}</span></div>
        <div class="pair">
            <div class="row"><span class="label">Departure time</span><span class="value">{{ $time($slip->departed_at) ?? ' ' }}</span></div>
            <div class="row"><span class="label">Arrival Time</span><span class="value">{{ $time($slip->arrived_at) ?? ' ' }}</span></div>
        </div>
        <div class="row"><span class="label">Patient's Name</span><span class="value">{{ $slip->patient_name }}</span></div>
        <div class="row"><span class="label">Transported to</span><span class="value">{{ $slip->transported_to }}</span></div>
        <div class="row"><span class="label">Ambulance Plate no.</span><span class="value">{{ $slip->plate_number }}</span></div>
        <div class="row"><span class="label">Driver</span><span class="value">{{ $slip->driver_name }}</span></div>
        <div class="staff">
            <div class="label">Names of Medical Staff:</div>
            <ol>
                @foreach ($slip->medicalStaffList() as $name)
                    <li>{{ $name }}</li>
                @endforeach
            </ol>
        </div>

        <div class="stamp">{{ $stampText }}</div>
        @if ($slip->status === 'rejected' && $slip->remarks)
            <p style="text-align:center;font:13px Arial,sans-serif;color:#b91c1c">Reason: {{ $slip->remarks }}</p>
        @endif

        <div class="sign">
            <div>Approved by:</div>
            <div class="name">{{ $valid ? ($slip->approver?->name ?? '—') : '' }}</div>
            <div>Hospital Administrator</div>
            @if ($valid && $slip->approved_at)
                <div class="meta">Approved {{ $slip->approved_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</div>
            @endif
        </div>

        <p class="foot">
            *Guard-on-duty shall record the time of departure and arrival
            @if ($slip->departedBy) — departure logged by {{ $slip->departedBy->name }}@endif
            @if ($slip->arrivedBy), arrival logged by {{ $slip->arrivedBy->name }}@endif
        </p>
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Back</a>
        @if ($valid)
            <button type="button" onclick="window.print()">Print</button>
        @endif
    </div>
</body>
</html>
