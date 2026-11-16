<?php

namespace App\Services\Hr;

use App\Models\Core\User;
use App\Models\Hr\Employee;
use App\Models\Store\Branch;

class AttendanceGeofenceService
{
    /**
     * Return whether a user's current location satisfies their branch geofence.
     * A missing branch configuration remains a non-blocking case, matching login behavior.
     *
     * @return array{allowed: bool, reason: ?string}
     */
    public function check(User $user, ?float $latitude, ?float $longitude): array
    {
        if ($user->isSuperAdmin() || $user->isOwner()) {
            return ['allowed' => true, 'reason' => null];
        }

        $employee = Employee::query()
            ->where('user_id', $user->id)
            ->where('store_id', $user->store_id)
            ->first();

        if (!$employee) {
            return ['allowed' => true, 'reason' => null];
        }

        $branchId = $user->branch_id ?: $employee->branch_id;
        $branch = $branchId
            ? Branch::query()->where('store_id', $user->store_id)->find($branchId)
            : null;
        $branch ??= Branch::query()
            ->where('store_id', $user->store_id)
            ->orderByDesc('is_main_branch')
            ->orderBy('id')
            ->first();

        if (!$branch || !$branch->geofence_enabled) {
            return ['allowed' => true, 'reason' => null];
        }

        if ($branch->latitude === null || $branch->longitude === null) {
            return ['allowed' => false, 'reason' => 'geofence_unconfigured'];
        }

        if ($latitude === null || $longitude === null) {
            return ['allowed' => false, 'reason' => 'location_required'];
        }

        return $this->isWithinRadius(
            $latitude,
            $longitude,
            (float) $branch->latitude,
            (float) $branch->longitude,
            (int) ($branch->geofence_radius_m ?? 5)
        )
            ? ['allowed' => true, 'reason' => null]
            : ['allowed' => false, 'reason' => 'outside_geofence'];
    }

    private function isWithinRadius(float $userLat, float $userLng, float $targetLat, float $targetLng, int $radiusMeters): bool
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($targetLat - $userLat);
        $dLng = deg2rad($targetLng - $userLng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($userLat)) * cos(deg2rad($targetLat)) * sin($dLng / 2) ** 2;
        $distance = $earthRadius * (2 * atan2(sqrt($a), sqrt(1 - $a)));

        return $distance <= $radiusMeters;
    }
}
