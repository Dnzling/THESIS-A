<?php

namespace App\Services\Inventory;

use App\Models\Inventory\ApprovalWorkflow;
use App\Models\Inventory\ApprovalStep;
use App\Models\Store\Store;
use Illuminate\Database\Query\Builder;
use Exception;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * Create approval process for an entity
     */
    public function createApprovalProcess(string $workflowType, int $entityId, float $amount, int $storeId): array
    {
        $workflow = ApprovalWorkflow::where('store_id', $storeId)
            ->where('workflow_type', $workflowType)
            ->active()
            ->first();

        if (!$workflow) {
            throw new Exception("No active workflow found for {$workflowType}");
        }

        // Check if auto-approves
        if ($workflow->autoApprovesForAmount($amount)) {
            return ['auto_approved' => true];
        }

        // Create approval steps
        $approvalsNeeded = [];
        foreach ($workflow->getApprovalStages() as $stage) {
            $step = ApprovalStep::create([
                'store_id' => $storeId,
                'entity_type' => $workflowType,
                'entity_id' => $entityId,
                'step_order' => $stage['order'],
                'required_role' => $stage['role'],
                'status' => 'pending',
            ]);

            $approvalsNeeded[] = [
                'order' => $stage['order'],
                'role' => $stage['role'],
                'entity_id' => $entityId,
                'entity_type' => $workflowType,
                'status' => 'pending',
                'required' => $stage['required'] ?? true,
                'step_id' => $step->id,
            ];
        }

        return $approvalsNeeded;
    }

    /**
     * Get required approvals for a workflow type and amount
     */
    public function getRequiredApprovals(string $workflowType, float $amount, int $storeId): array
    {
        $workflow = ApprovalWorkflow::where('store_id', $storeId)
            ->where('workflow_type', $workflowType)
            ->active()
            ->first();

        if (!$workflow) {
            return [];
        }

        if ($workflow->autoApprovesForAmount($amount)) {
            return [];
        }

        $stages = $workflow->getApprovalStages();
        $roles = [];

        foreach ($stages as $stage) {
            if ($stage['required'] ?? true) {
                $roles[] = $stage['role'];
            }
        }

        return $roles;
    }

    /**
     * Check if all approvals are complete for an entity
     */
    public function checkAllApprovalsComplete(string $entityType, int $entityId, int $storeId): bool
    {
        $steps = ApprovalStep::where('store_id', $storeId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->get();

        if ($steps->isEmpty()) {
            return true; // No approvals required
        }

        $pending = $steps->where('status', 'pending')->count();
        $rejected = $steps->where('status', 'rejected')->count();

        return $pending === 0 && $rejected === 0;
    }

    /**
     * Get pending approvals for an role/user
     */
    public function getPendingApprovalsForRole(int $storeId, string $role): \Illuminate\Database\Eloquent\Collection
    {
        return ApprovalStep::where('store_id', $storeId)
            ->where('required_role', $role)
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Approve a step
     */
    public function approveStep(int $stepId, ?string $notes = null): ApprovalStep
    {
        $step = ApprovalStep::findOrFail($stepId);
        $step->approve($notes);
        return $step;
    }

    /**
     * Reject a step
     */
    public function rejectStep(int $stepId, ?string $notes = null): ApprovalStep
    {
        $step = ApprovalStep::findOrFail($stepId);
        $step->reject($notes);
        return $step;
    }

    /**
     * Get approval status for an entity
     */
    public function getApprovalStatus(string $entityType, int $entityId, int $storeId): array
    {
        return [
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => 'pending',
            'completed_approvals' => [],
            'pending_approvals' => [],
        ];
    }

    /**
     * Approve a step in the workflow
     */
    public function approveStep(string $entityType, int $entityId, string $approverRole): bool
    {
        // Logic to mark approval step as complete
        return true;
    }

    /**
     * Cancel approval process (for rejected/cancelled transfers)
     */
    public function cancelApprovalProcess(string $workflowType, int $entityId): void
    {
        // Clean up any pending approvals for this entity
    }

    /**
     * Get workflows for a store
     */
    public function getStoreWorkflows(int $storeId): array
    {
        return ApprovalWorkflow::where('store_id', $storeId)
            ->get()
            ->groupBy('workflow_type')
            ->toArray();
    }

    /**
     * Update workflow configuration
     */
    public function updateWorkflow(int $storeId, string $workflowType, array $config): ApprovalWorkflow
    {
        $workflow = ApprovalWorkflow::where('store_id', $storeId)
            ->where('workflow_type', $workflowType)
            ->first();

        if (!$workflow) {
            $workflow = new ApprovalWorkflow([
                'store_id' => $storeId,
                'workflow_type' => $workflowType,
            ]);
        }

        $workflow->update($config);
        return $workflow;
    }

    /**
     * Determine if finance approval is needed
     */
    public function requiresFinanceApproval(string $workflowType, float $amount, int $storeId): bool
    {
        $workflow = ApprovalWorkflow::where('store_id', $storeId)
            ->where('workflow_type', $workflowType)
            ->active()
            ->first();

        if (!$workflow) {
            return false;
        }

        return $workflow->requiresRole('finance_manager') && $amount >= $workflow->minimum_amount_trigger;
    }
}
