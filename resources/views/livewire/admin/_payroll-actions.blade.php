{{-- Row actions for one payroll period: the step it's waiting on, its payslips, the rest under "More". --}}
@php $hasPayslips = in_array($period->status, ['processed', 'finalized']); @endphp

@if ($period->status === 'draft')
    <button wire:click="openGenerate({{ $period->id }})" class="tbl-action tbl-action-primary">Generate</button>
@elseif ($period->status === 'processed')
    <button wire:click="finalize({{ $period->id }})" wire:confirm="Finalize this payroll? This cannot be undone. Employees will be texted that their payslip is ready." class="tbl-action tbl-action-success">Finalize</button>
@elseif ($period->status === 'finalized')
    <button wire:click="sendPayslipSms({{ $period->id }})" wire:confirm="Text a payslip notice to every employee in this period who hasn't been notified yet?" class="tbl-action tbl-action-primary">SMS Payslips</button>
@endif

@if ($hasPayslips)
    <button wire:click="viewPayslips({{ $period->id }})" class="tbl-action">Payslips</button>
@endif

<x-table-actions-more label="More actions for {{ $period->name }}">
    @if ($period->status === 'processed')
        <button wire:click="openGenerate({{ $period->id }})" role="menuitem" class="tbl-menu-item">
            <x-phosphor-arrows-clockwise class="w-4 h-4 text-gray-400" /> Regenerate
        </button>
    @endif
    @if ($hasPayslips)
        <button wire:click="exportExcel({{ $period->id }})" role="menuitem" class="tbl-menu-item">
            <x-phosphor-file-xls class="w-4 h-4 text-gray-400" /> Export to Excel
        </button>
    @endif
    @if ($period->status !== 'finalized')
        <button wire:click="openImport({{ $period->id }})" role="menuitem" class="tbl-menu-item">
            <x-phosphor-upload-simple class="w-4 h-4 text-gray-400" /> Import Excel
        </button>
    @endif
</x-table-actions-more>
