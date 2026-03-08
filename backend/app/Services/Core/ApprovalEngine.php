<?php

namespace App\Services\Core;

use App\Models\Core\ApprovalRule;
use App\Models\Core\ApprovalTask;
use App\Models\Core\ApprovalWorkflow;
use App\Models\Core\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ApprovalEngine
{
    public function __construct(protected PermissionService $permissionService)
    {
    }

    /**
     * Process transaction approval rules and generate workflow/task records.
     */
    public function process($transaction, string $action, $user, int $storeId): array
    {
        $context = $this->buildContext($transaction, $action, $user, $storeId);

        /** @var \Illuminate\Database\Eloquent\Collection<int, ApprovalRule> $rules */
        $rules = ApprovalRule::query()
            ->where('trigger_event', $action)
            ->where('is_active', true)
            ->where(function ($q) use ($storeId): void {
                $q->whereNull('store_id')->orWhere('store_id', $storeId);
            })
            ->orderBy('priority')
            ->get();

        if ($rules->isEmpty()) {
            return $this->applyDefaultBehavior($transaction, $context);
        }

        return DB::transaction(function () use ($transaction, $user, $context, $rules): array {
            $assignments = [];
            $finalStatus = 'pending';
            $ruleNotes = [];

            foreach ($rules as $rule) {
                if (!$rule->evaluate($context)) {
                    continue;
                }

                $execution = $rule->execute($transaction, $context);

                if (!empty($execution['notes'])) {
                    $ruleNotes = array_merge($ruleNotes, $execution['notes']);
                }

                if (!empty($execution['assigned_to'])) {
                    $assignments = array_merge($assignments, $execution['assigned_to']);
                }

                if ($execution['rejected'] ?? false) {
                    $finalStatus = 'rejected';
                    break;
                }

                if ($execution['auto_approved'] ?? false) {
                    $finalStatus = 'auto_approved';
                    break;
                }

                if ($rule->isTerminal()) {
                    break;
                }
            }

            $assignments = array_values(array_unique($assignments));

            $workflow = ApprovalWorkflow::create([
                'workflowable_type' => $transaction::class,
                'workflowable_id' => $transaction->getKey(),
                'current_step' => 1,
                'status' => $finalStatus,
                'created_by' => $user->id,
                'approved_by' => in_array($finalStatus, ['approved', 'auto_approved'], true) ? $user->id : null,
                'approved_at' => in_array($finalStatus, ['approved', 'auto_approved'], true) ? now() : null,
                'workflow_data' => [
                    'action' => $context['action'],
                    'trigger_event' => $context['trigger_event'],
                    'store_id' => $context['store_id'],
                    'amount' => $context['amount'],
                ],
                'notes' => empty($ruleNotes) ? null : implode(' | ', $ruleNotes),
            ]);

            foreach ($assignments as $assignedTo) {
                ApprovalTask::create([
                    'approval_workflow_id' => $workflow->id,
                    'assigned_to' => $assignedTo,
                    'status' => 'pending',
                ]);
            }

            $transaction->approval_workflow_id = $workflow->id;
            $transaction->requires_approval = !in_array($finalStatus, ['approved', 'auto_approved'], true);
            $transaction->approval_status = $finalStatus;

            if (in_array($finalStatus, ['approved', 'auto_approved'], true)) {
                $transaction->approved_by = $user->id;
                $transaction->approved_at = now();
            }

            $transaction->save();

            return [
                'status' => $finalStatus,
                'workflow_id' => $workflow->id,
                'requires_approval' => (bool) $transaction->requires_approval,
                'tasks_created' => count($assignments),
                'notes' => $ruleNotes,
            ];
        });
    }

    protected function buildContext($transaction, string $action, $user, int $storeId): array
    {
        $amount = (float) ($transaction->total_value ?? $transaction->estimated_amount ?? 0);
        $module = str_contains($action, '.') ? explode('.', $action)[0] : 'inventory';

        return [
            'store_id' => $storeId,
            'module' => $module,
            'action' => $action,
            'trigger_event' => $action,
            'amount' => $amount,
            'user_id' => $user->id,
            'role_id' => $user->role_id,
            'is_dual_role' => $user instanceof User
                ? $user->isDualRoleFor('approve', $module, $storeId)
                : false,
            'transaction_type' => $transaction->transaction_type ?? null,
        ];
    }

    protected function applyDefaultBehavior($transaction, array $context): array
    {
        /** @var User|null $user */
        $user = Auth::user();

        $canApprove = $user instanceof User
            ? $this->permissionService->userHasPermission($user, $context['module'] . '.all.approve', $context['store_id'])
            : false;

        $autoApprove = $canApprove || ($context['is_dual_role'] ?? false) === true;

        $workflow = ApprovalWorkflow::create([
            'workflowable_type' => $transaction::class,
            'workflowable_id' => $transaction->getKey(),
            'current_step' => 1,
            'status' => $autoApprove ? 'auto_approved' : 'pending',
            'created_by' => $user?->id,
            'approved_by' => $autoApprove ? $user?->id : null,
            'approved_at' => $autoApprove ? now() : null,
            'workflow_data' => $context,
            'notes' => $autoApprove
                ? 'Auto-approved by default behavior (dual-role/approve permission).'
                : 'Pending manual approval (default behavior).',
        ]);

        $transaction->approval_workflow_id = $workflow->id;
        $transaction->requires_approval = !$autoApprove;
        $transaction->approval_status = $workflow->status;

        if ($autoApprove) {
            $transaction->approved_by = $user?->id;
            $transaction->approved_at = now();
        }

        $transaction->save();

        return [
            'status' => $workflow->status,
            'workflow_id' => $workflow->id,
            'requires_approval' => (bool) $transaction->requires_approval,
            'tasks_created' => 0,
            'notes' => [$workflow->notes],
        ];
    }
}
