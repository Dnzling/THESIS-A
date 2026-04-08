<?php

namespace App\Models\Core;

use App\Models\Store\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRule extends Model
{
    protected $fillable = [
        'name',
        'trigger_event',
        'conditions',
        'actions',
        'priority',
        'store_id',
        'is_active',
        'created_by',
        'description',
    ];

    protected $casts = [
        'conditions' => 'array',
        'actions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Evaluate whether this rule applies to the given runtime context.
     */
    public function evaluate(array $context): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $conditions = $this->conditions ?? [];

        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '==';
            $expected = $condition['value'] ?? null;

            if (!$field) {
                continue;
            }

            $actual = data_get($context, $field);

            if (!$this->compare($actual, $operator, $expected)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute rule actions for a transaction/workflow target.
     */
    public function execute($transaction, array $context = []): array
    {
        $actions = $this->actions ?? [];
        $result = [
            'auto_approved' => false,
            'rejected' => false,
            'assigned_to' => [],
            'notes' => [],
        ];

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;

            if ($type === 'auto_approve') {
                $result['auto_approved'] = true;
                $result['notes'][] = $action['message'] ?? 'Auto-approved by rule.';
            }

            if ($type === 'reject') {
                $result['rejected'] = true;
                $result['notes'][] = $action['message'] ?? 'Rejected by rule.';
            }

            if ($type === 'assign_to_users') {
                $result['assigned_to'] = array_merge($result['assigned_to'], $action['user_ids'] ?? []);
            }

            if ($type === 'assign_to_role') {
                $role = $action['role'] ?? null;
                $storeId = $context['store_id'] ?? null;

                if ($role) {
                    $query = User::query()->whereHas('role', fn ($q) => $q->where('name', $role));

                    if ($storeId) {
                        $query->where('store_id', $storeId);
                    }

                    $result['assigned_to'] = array_merge(
                        $result['assigned_to'],
                        $query->pluck('id')->all()
                    );
                }
            }
        }

        $result['assigned_to'] = array_values(array_unique($result['assigned_to']));

        return $result;
    }

    /**
     * Determine if the rule can finish a workflow immediately.
     */
    public function isTerminal(): bool
    {
        $actions = $this->actions ?? [];

        foreach ($actions as $action) {
            if (in_array(($action['type'] ?? ''), ['auto_approve', 'reject'], true)) {
                return true;
            }
        }

        return false;
    }

    protected function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '==' => $actual == $expected,
            '===' => $actual === $expected,
            '!=' => $actual != $expected,
            '!==' => $actual !== $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'in' => is_array($expected) && in_array($actual, $expected, true),
            'not_in' => is_array($expected) && !in_array($actual, $expected, true),
            default => false,
        };
    }
}
