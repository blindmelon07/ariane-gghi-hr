@php
    $d = fn ($date) => $date?->format('m/d/Y') ?? '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>COE Request — {{ $coe->employee->full_name }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: 'Times New Roman', Georgia, serif; color: #111827; font-size: 14px; }
        .sheet { max-width: 640px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .head { text-align: center; margin-bottom: 14px; } .head img { height: 46px; }
        table { width: 100%; border-collapse: collapse; }
        td { border: 1px solid #111827; padding: 4px 6px 8px; vertical-align: top; }
        .cap { font-size: 11px; text-transform: uppercase; display: block; }
        .val { font-family: Arial, sans-serif; font-weight: bold; min-height: 18px; display: block; margin-top: 3px; white-space: pre-line; }
        .title { text-align: center; font-weight: bold; padding: 6px; }
        .section { font-weight: bold; font-size: 12px; }
        .status { margin: 14px auto 0; text-align: center; font: bold 15px Arial, sans-serif; color: #374151; }
        .actions { max-width: 640px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; font-family: Arial, sans-serif; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 520px) { body { padding: 6px; font-size: 13px; } .sheet { padding: 14px 10px; } }
        @media print { body { background: #fff; padding: 0; } .sheet { box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head"><img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc."></div>

        <table>
            <tr><td colspan="4" class="title">COE REQUEST FORM</td></tr>
            <tr>
                <td colspan="3"><span class="cap">Complete Name of Employee</span><span class="val">{{ $coe->employee->full_name }}</span></td>
                <td><span class="cap">Date</span><span class="val">{{ $coe->created_at->timezone('Asia/Manila')->format('m/d/Y') }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="cap">Department</span><span class="val">{{ $coe->department }}</span></td>
                <td colspan="2"><span class="cap">Designation</span><span class="val">{{ $coe->designation }}</span></td>
            </tr>
            <tr>
                <td colspan="2"><span class="cap">Date Hired</span><span class="val">{{ $d($coe->date_hired) }}</span></td>
                <td colspan="2"><span class="cap">Date Separated (if applicable)</span><span class="val">{{ $d($coe->date_separated) }}</span></td>
            </tr>
            <tr><td colspan="4"><span class="cap">Reason for COE Request:</span><span class="val">{{ $coe->reason }}</span></td></tr>
            <tr><td colspan="4"><span class="cap">Additional Requested Details to be Indicated in the COE:</span><span class="val">{{ $coe->additional_details }}</span></td></tr>
            <tr><td colspan="4"><span class="cap">Signature of Employee/Date</span><span class="val">{{ $coe->signature_name }} — {{ $coe->created_at->timezone('Asia/Manila')->format('m/d/Y') }}</span></td></tr>
            <tr><td colspan="4" class="section">COE RELEASING DETAILS</td></tr>
            <tr>
                <td><span class="cap">COE Released By:</span><span class="val">{{ $coe->released_at ? $coe->releaser?->name : '' }}</span></td>
                <td><span class="cap">Release Date:</span><span class="val">{{ $d($coe->released_at) }}</span></td>
                <td><span class="cap">COE Received By:</span><span class="val">{{ $coe->received_by_name }}</span></td>
                <td><span class="cap">Receive Date:</span><span class="val">{{ $d($coe->received_at) }}</span></td>
            </tr>
        </table>

        <div class="status">
            Status: {{ \App\Models\CoeRequest::STATUS_LABELS[$coe->status] ?? $coe->status }}
            @if ($coe->remarks) <div style="font-weight:normal;font-size:13px;margin-top:4px">Remarks: {{ $coe->remarks }}</div> @endif
        </div>
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Back</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>
</html>
