<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use App\Models\Core\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $query = User::query()
            ->superAdmins()
            ->with('role')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('fname', 'like', "%{$search}%")
                    ->orWhere('lname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $admins = $query->get()->map(function (User $user) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'created_at' => optional($user->created_at)->format('Y-m-d H:i:s'),
            ];
        })->values();

        return response()->json(['success' => true, 'data' => $admins]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!auth()->user()?->hasRole('super_admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        $role = Role::query()->where('name', 'super_admin')->first();
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Super admin role not found.'], 422);
        }

        $user = User::create([
            'user_id' => User::generateUserId(),
            'fname' => $validated['fname'],
            'lname' => $validated['lname'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $role->id,
            'is_active' => true,
            'registered_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Super admin created successfully.',
            'data' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'is_active' => (bool) $user->is_active,
                'created_at' => optional($user->created_at)->format('Y-m-d H:i:s'),
            ],
        ], 201);
    }
}
