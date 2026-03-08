<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isAdmin = $request->user() && $request->user()->hasRole('super_admin');
        $isStoreAdmin = $request->user() && $request->user()->hasRole('store_admin');
        $isHR = $request->user() && $request->user()->hasRole('hr');
        $isManager = $request->user() && $request->user()->hasRole('manager');
        
        $canViewSensitive = $isAdmin || $isStoreAdmin || $isHR || $isManager;
        
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'first_name' => $this->fname,
            'last_name' => $this->lname,
            'email' => $this->email,
            'phone_number' => $this->when($canViewSensitive, $this->phone_number),
            'is_active' => (bool) $this->is_active,
            
            // Role info

            'role' => $this->role->name,
            'display_role' => $this->role->display_name,
            // 'role' => $this->whenLoaded('role', function () {
            //     return [
            //         'id' => $this->role->id,
            //         'name' => $this->role->name,
            //         'display_name' => $this->role->display_name,
            //     ];
            // }),
            
            // Store info - only show if allowed
            'store' => $this->when($canViewSensitive && $this->relationLoaded('store'), function () {
                return $this->store ? [
                    'id' => $this->store->id,
                    'name' => $this->store->store_name,
                    'code' => $this->store->store_code,
                ] : null;
            }),
            
            // Branch info - only show if allowed
            'branch' => $this->when($canViewSensitive && $this->relationLoaded('branch'), function () {
                return $this->branch ? [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'code' => $this->branch->branch_code,
                ] : null;
            }),
            
            // Admin only fields
            'email_verified_at' => $this->when($isAdmin, 
                $this->email_verified_at?->format('Y-m-d H:i:s')
            ),
            'last_login_at' => $this->when($isAdmin, 
                $this->last_login_at?->format('Y-m-d H:i:s')
            ),
            'registered_by' => $this->when($isAdmin, $this->registered_by),
            'deleted_by' => $this->when($isAdmin, $this->deleted_by),
            'deleted_at' => $this->when($isAdmin, 
                $this->deleted_at?->format('Y-m-d H:i:s')
            ),
            
            // Timestamps
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->when($canViewSensitive, 
                $this->updated_at->format('Y-m-d H:i:s')
            ),
        ];
    }
}