<?php

namespace App\Models\Core;

use App\Models\Hr\Department;
use App\Models\Store\Branch;
use App\Models\Store\Store;
use App\Models\Core\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'branch_id',
        'department_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'meta',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public static function record(string $action, ?string $description = null, array $meta = [], ?string $entityType = null, ?int $entityId = null, ?int $departmentId = null): self
    {
        $user = Auth::user();
        $storeId = $user?->store_id;
        $branchId = $user?->branch_id;

        if (!$departmentId && $user?->employee?->department) {
            $departmentId = Department::where('name', $user->employee->department)->value('id');
        }

        return self::create([
            'user_id' => $user?->id,
            'store_id' => $storeId,
            'branch_id' => $branchId,
            'department_id' => $departmentId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'meta' => $meta,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
}
