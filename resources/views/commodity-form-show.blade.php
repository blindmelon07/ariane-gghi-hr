@php
    use App\Models\CommodityAllowanceForm;
    $box = fn (bool $on) => $on ? '☒' : '☐';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commodity Allowance Form — {{ $form->fullName() }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 16px; background: #f3f4f6; font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 15px; }
        .sheet { max-width: 560px; margin: 0 auto; background: #fff; padding: 24px 24px 30px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        .head { text-align: center; } .head img { height: 44px; }
        h1 { text-align: center; font-size: 17px; margin: 12px 0 18px; }
        .row { display: flex; gap: 8px; margin: 11px 0; }
        .row .label { width: 170px; flex-shrink: 0; }
        .row .value { flex: 1; border-bottom: 1px solid #6b7280; padding: 0 4px 2px; font-weight: bold; }
        .choices span { margin-right: 14px; white-space: nowrap; }
        .sign { margin-top: 36px; text-align: center; }
        .sign .line { display: inline-block; min-width: 280px; border-bottom: 1px solid #111827; font-weight: bold; padding: 0 8px 2px; }
        .meta { font-size: 11px; color: #6b7280; margin-top: 4px; }
        .actions { max-width: 560px; margin: 14px auto 0; display: flex; gap: 10px; justify-content: center; }
        .actions a, .actions button { font-size: 14px; padding: 9px 16px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; color: #111827; text-decoration: none; cursor: pointer; }
        .actions button { background: #4f46e5; border-color: #4f46e5; color: #fff; }
        @media (max-width: 480px) { body { padding: 6px; font-size: 14px; } .sheet { padding: 16px 12px; } .row { flex-direction: column; gap: 2px; } .row .label { width: auto; font-size: 12px; } }
        @media print { body { background: #fff; padding: 0; } .sheet { box-shadow: none; } .actions { display: none; } }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="head"><img src="{{ asset('images/gghi logo.png') }}" alt="GSAC General Hospital Inc."></div>
        <h1>Commodity Allowance Form</h1>

        <div class="row"><span class="label">Last Name:</span><span class="value">{{ $form->last_name }}</span></div>
        <div class="row"><span class="label">First Name:</span><span class="value">{{ $form->first_name }}</span></div>
        <div class="row"><span class="label">Middle Name:</span><span class="value">{{ $form->middle_name ?: ' ' }}</span></div>
        <div class="row"><span class="label">Position:</span><span class="value">{{ $form->position }}</span></div>
        <div class="row"><span class="label">Address:</span><span class="value">{{ $form->address }}</span></div>
        <div class="row"><span class="label">Religion:</span><span class="value">{{ $form->religion ?: ' ' }}</span></div>
        <div class="row"><span class="label">Birthdate:</span><span class="value">{{ $form->birthdate->format('F d, Y') }}</span></div>
        <div class="row choices"><span class="label">Civil Status:</span>
            <span>@foreach (CommodityAllowanceForm::CIVIL_STATUSES as $value => $text)<span>{{ $box($form->civil_status === $value) }} {{ $text }}</span>@endforeach</span>
        </div>
        <div class="row"><span class="label">Name of Spouse:</span><span class="value">{{ $form->spouse_name ?: ' ' }}</span></div>
        <div class="row"><span class="label">Birthdate of Spouse:</span><span class="value">{{ $form->spouse_birthdate?->format('F d, Y') ?: ' ' }}</span></div>
        <div class="row choices"><span class="label">GSAC Membership:</span>
            <span>@foreach (CommodityAllowanceForm::MEMBERSHIPS as $value => $text)<span>{{ $box($form->gsac_membership === $value) }} {{ $text }}</span>@endforeach</span>
        </div>
        <div class="row"><span class="label">Passbook Number:</span><span class="value">{{ $form->passbook_number ?: ' ' }}</span></div>

        <div class="sign">
            <div class="line">{{ $form->signature_name }}</div>
            <div>Name and Signature</div>
            <div class="meta">Signed electronically {{ $form->submitted_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</div>
        </div>
    </div>

    <div class="actions">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}">Back</a>
        <button type="button" onclick="window.print()">Print</button>
    </div>
</body>
</html>
