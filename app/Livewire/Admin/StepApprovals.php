<?php

namespace App\Livewire\Admin;

use App\Services\ActivityLogService;
use App\Services\LeaveService;
use App\Services\SmsNotificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Approval queue for employee requests that follow the leave approval chain
 * (LeaveService::APPROVAL_STEPS): each step approves in turn, super admins can
 * approve outright, rejecting needs remarks. Subclasses describe the request.
 */
abstract class StepApprovals extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $search       = '';

    public ?int   $selectedId   = null;
    public string $actionType   = '';
    public string $remarks      = '';

    /** @return class-string<Model> */
    abstract protected function model(): string;

    /** Singular name shown to users, e.g. "shift change". */
    abstract protected function label(): string;

    /** Column the queue is sorted by (newest first). */
    abstract protected function dateColumn(): string;

    /** Route the employee files and tracks this request on. */
    abstract protected function employeeRoute(): string;

    /** Short description used in SMS and the activity log. */
    abstract protected function describe(Model $request): string;

    /** @return array<string, string> label => value columns for the queue */
    abstract public function details(Model $request): array;

    /** Relations to eager-load for the queue. */
    protected function relations(): array
    {
        return ['employee'];
    }

    /** Approval chain: step => ['role' => string|string[], 'label' => string]. */
    protected function steps(): array
    {
        return LeaveService::APPROVAL_STEPS;
    }

    /** Error that blocks final approval, if the request no longer makes sense. */
    protected function finalApprovalError(Model $request): ?string
    {
        return null;
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }

    #[Computed]
    public function approvalSteps(): array
    {
        return $this->steps();
    }

    #[Computed]
    public function myStep(): ?int
    {
        $role = Auth::user()?->role;

        foreach ($this->steps() as $step => $config) {
            if (in_array($role, (array) $config['role'], true)) {
                return $step;
            }
        }

        return null;
    }

    /** Super admins can approve or reject at any step, without waiting for the others. */
    #[Computed]
    public function canOverride(): bool
    {
        return Auth::user()?->role === 'super_admin';
    }

    #[Computed]
    public function title(): string
    {
        return ucwords($this->label());
    }

    #[Computed]
    public function requests()
    {
        return $this->model()::with($this->relations())
            ->when($this->filterStatus === 'pending', fn ($q) =>
                $q->where('status', 'pending')->when(! $this->canOverride, fn ($q) => $q->where('approval_step', $this->myStep))
            )
            ->when($this->filterStatus !== 'pending' && $this->filterStatus !== 'all',
                fn ($q) => $q->where('status', $this->filterStatus)
            )
            ->when($this->search, fn ($q) =>
                $q->whereHas('employee', fn ($eq) =>
                    $eq->where('first_name', 'like', "%{$this->search}%")
                       ->orWhere('last_name', 'like', "%{$this->search}%")
                       ->orWhere('emp_code', 'like', "%{$this->search}%")
                )
            )
            ->orderByDesc($this->dateColumn())
            ->paginate(20);
    }

    public function openAction(int $id, string $type): void
    {
        $this->selectedId = $id;
        $this->actionType = $type;
        $this->remarks    = '';
        $this->resetErrorBag();
        $this->dispatch('open-approval-modal');
    }

    public function confirmAction(): void
    {
        $request = $this->model()::with('employee')->find($this->selectedId);
        if (! $request || $request->status !== 'pending') return;

        if ($request->approval_step !== $this->myStep && ! $this->canOverride) {
            $this->addError('remarks', 'This request is not at your approval step.');
            return;
        }

        $user = Auth::user();
        if ($user->employee_code && $request->employee->emp_code === $user->employee_code) {
            $this->addError('remarks', 'You cannot approve or reject your own request.');
            $this->dispatch('open-approval-modal');
            return;
        }

        $steps    = $this->steps();
        $notifier = app(SmsNotificationService::class);
        $what     = $this->describe($request);
        $link     = route($this->employeeRoute());
        $context  = str_replace(' ', '-', $this->label()) . ":{$request->id}";
        $logKey   = str_replace(' ', '_', $this->label());
        $employee = $request->employee;

        if ($this->actionType === 'approve') {
            $nextStep = $request->approval_step + 1;
            $override = $this->canOverride && $request->approval_step !== $this->myStep;
            $isFinal  = ! isset($steps[$nextStep]) || $this->canOverride;   // super admin approves outright

            if ($isFinal) {
                if ($error = $this->finalApprovalError($request)) {
                    $this->addError('remarks', "Cannot approve: {$error} Reject the request instead.");
                    $this->dispatch('open-approval-modal');
                    return;
                }

                $request->update([
                    'status'        => 'approved',
                    'approval_step' => null,
                    'approved_by'   => $user->id,
                    'approved_at'   => now(),
                    'remarks'       => $this->remarks ?: null,
                ]);
                ActivityLogService::log("{$logKey}_approved", ucfirst($what) . ' approved' . ($override ? ' (Super Admin override)' : '') . " for {$employee->full_name}", $employee);
                $message = ucfirst($this->label()) . ($override ? ' approved (Super Admin override).' : ' fully approved.');
                $notifier->requestDecision($employee, $what, true, $this->remarks ?: null, $link, $context);
            } else {
                $request->update(['approval_step' => $nextStep]);
                ActivityLogService::log("{$logKey}_step", "Step {$request->approval_step} approved {$what} for {$employee->full_name}", $employee);
                $message = 'Approved — forwarded to ' . $steps[$nextStep]['label'] . '.';
                $notifier->requestProgress($employee, $what, $steps[$nextStep - 1]['label'], $steps[$nextStep]['label'], $link, $context);
            }

            $this->dispatch('toast', message: $message);
        } else {
            if (empty($this->remarks)) {
                $this->addError('remarks', 'Reason is required when rejecting.');
                $this->dispatch('open-approval-modal');
                return;
            }

            $request->update([
                'status'        => 'rejected',
                'approval_step' => null,
                'approved_by'   => $user->id,
                'approved_at'   => now(),
                'remarks'       => $this->remarks,
            ]);
            ActivityLogService::log("{$logKey}_rejected", ucfirst($what) . " rejected for {$employee->full_name}", $employee);
            $notifier->requestDecision($employee, $what, false, $this->remarks, $link, $context);
            $this->dispatch('toast', message: ucfirst($this->label()) . ' rejected.');
        }

        $this->selectedId = null;
        $this->actionType = '';
        $this->remarks    = '';
        unset($this->requests, $this->myStep);
    }

    public function render()
    {
        return view('livewire.admin.step-approvals');
    }
}
