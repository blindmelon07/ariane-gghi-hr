@php
    $emp      = $form->employee;
    $approved = $form->status === 'approved';
    [$stampText, $stampColor] = match ($form->status) {
        'approved' => ['APPROVED — DEDUCTIONS ACTIVE ON PAYROLL', '#15803d'],
        'pending'  => ['PENDING — FOR HR APPROVAL', '#b45309'],
        default    => [strtoupper($form->status), '#b91c1c'],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Salary Deduction Form #{{ $form->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 13px; }
        .sheet { max-width: 680px; margin: 0 auto; background: #fff; padding: 22px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .head { text-align: center; } .head img { height: 44px; }
        h1 { text-align: center; font-size: 16px; margin: 12px 0; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 6px 18px; margin-bottom: 12px; }
        .meta span { border-bottom: 1px solid #6b7280; font-weight: bold; padding: 0 4px; }
        .auth { margin: 10px 0 12px; line-height: 1.45; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #374151; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .right { text-align: right; }
        .muted { color: #6b7280; font-size: 11px; }
        .note { font-style: italic; margin-top: 6px; }
        .sign { margin-top: 18px; display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .sign .line { min-width: 220px; border-bottom: 1px solid #111827; text-align: center; font-weight: bold; padding: 14px 6px 2px; }
        .stamp { margin: 16px auto 0; text-align: center; font: bold 15px Arial, sans-serif; border: 3px solid {{ $stampColor }}; color: {{ $stampColor }}; border-radius: 6px; padding: 6px 10px; max-width: 460px; }
        .actions { max-width: 680px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 520px) { body { padding: 6px; font-size: 12px; } .sheet { padding: 14px 10px; } .meta { grid-template-columns: 1fr; } }
        @media print { body { background: #fff; padding: 0; } .sheet { box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head"><img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc."></div>
        <h1>SALARY DEDUCTION FORM</h1>

        <div class="meta">
            <div>Name of the employee: <span>{{ $emp->full_name }}</span></div>
            <div>Date of Request: <span>{{ $form->created_at->timezone('Asia/Manila')->format('m/d/Y') }}</span></div>
            <div>Position: <span>{{ $emp->position ?: '—' }}</span></div>
            <div>Department: <span>{{ $emp->department ?: '—' }}</span></div>
        </div>

        <p class="auth">I hereby authorize the HR Staff to deduct the particular/s specified hereunder from my salary and no changes shall be made unless financial obligation/s due to me is already fully paid or such is supported by another Salary Deduction Form signed by me.</p>

        <table>
            <tr><th>Particulars</th><th class="right">Amount (Php)</th><th>Cut-off Date/Period</th></tr>
            @foreach ($form->lines as $line)
                <tr>
                    <td>{{ $line->label() }}</td>
                    <td class="right">
                        {{ number_format((float) $line->amount_per_cutoff, 2) }} / cut-off
                        @if ($line->total_amount !== null)
                            <div class="muted">Total {{ number_format((float) $line->total_amount, 2) }}
                                @if ($line->deduction?->auto_stop) · Balance {{ number_format((float) $line->deduction->remaining_balance, 2) }}@endif
                            </div>
                        @endif
                    </td>
                    <td>{{ $line->periodLabel() }}</td>
                </tr>
            @endforeach
        </table>
        <p class="note">For loan deductions, please attach the amortization table issued by the bank/agency.
            @if ($form->attachment_path)
                — <a href="{{ route('salary-deduction.attachment', $form) }}" target="_blank">View attached amortization table</a>
            @endif
        </p>

        <div class="sign">
            <div>Requested by:<div class="line">{{ $emp->full_name }}</div><div style="text-align:center">(Employee)</div></div>
            <div>Noted by:<div class="line">{{ $approved ? $form->approver?->name : '' }}</div><div style="text-align:center">(HR Personnel)
                Date: {{ $approved && $form->approved_at ? $form->approved_at->timezone('Asia/Manila')->format('m/d/Y') : '__/__/____' }}</div></div>
        </div>

        <div class="stamp">{{ $stampText }}</div>
        @if ($form->status === 'rejected' && $form->remarks)
            <p style="text-align:center;color:#b91c1c">Reason: {{ $form->remarks }}</p>
        @endif
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Back</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>
</html>
