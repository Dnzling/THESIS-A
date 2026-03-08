<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use App\Models\Core\User;
// use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $currentUser = auth()->user();
            if (!$currentUser) return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);

            $currentUser->load('role');
            $query = User::query();

            // Determine scope based on role
            if ($currentUser->hasRole('super_admin')) {
                $query->withTrashed(); // Can see everything including deleted

            } elseif ($currentUser->hasRole('store_admin') || $currentUser->hasRole('hr_manager')) {
                // Can see users in same store
                if (!$currentUser->store_id) {
                    return response()->json(['success' => false, 'message' => 'No store assigned'], 400);
                }
                $query->where('store_id', $currentUser->store_id);
            } elseif ($currentUser->hasRole('manager')) {
                // Can see users in same branch
                if (!$currentUser->store_id || !$currentUser->branch_id) {
                    return response()->json(['success' => false, 'message' => 'No store/branch assigned'], 400);
                }
                $query->where('store_id', $currentUser->store_id)
                    ->where('branch_id', $currentUser->branch_id);
            } else {
                // Regular employees can only see themselves
                $query->where('id', $currentUser->id);
            }

            // Apply filters if user has permission
            if ($currentUser->hasAnyRole(['super_admin', 'store_admin', 'hr'])) {
                if ($request->store_id) $query->where('store_id', $request->store_id);
                if ($request->role_id) $query->where('role_id', $request->role_id);
                if ($request->is_active !== null) $query->where('is_active', $request->is_active);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('fname', 'like', "%{$request->search}%")
                        ->orWhere('lname', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%");
                });
            }

            if (!$currentUser->hasRole('super_admin')) {
                $query->whereNull('deleted_at');
            }

            $users = $query->with(['role', 'store', 'branch'])
                ->orderBy('created_at', 'desc')
                ->paginate($request->per_page ?? 20);

            return response()->json([
                'success' => true,
                'data' => UserResource::collection($users),
                'message' => 'Users retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users'
            ], 500);
        }
    }
    public function store(Request $request)
    {
        try {
            // Get user data from request (sent from frontend)
            $validated = $request->validate([
                'fname' => 'required|string|max:255',
                'lname' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'role' => 'required|in:manager,clerk,sales',
                'password' => 'required|string|max:255',
                'branch_id' => 'nullable|exists:branches,id',
                'phone_number' => 'nullable|string',

                // Current user data from frontend
                'current_user_id' => 'required|exists:users,id'
            ]);

            $creatorUser = User::find($validated['current_user_id']);

            // Determine store_id for new staff
            $storeIdForNewStaff = null;

            if ($creatorUser->hasRole('hr_manager')) {
                // HR can assign to any store - need store_id from request
                $request->validate([
                    'store_id' => 'required|exists:stores,id'
                ]);
                $storeIdForNewStaff = $request->id;
            } elseif ($creatorUser->hasRole('store_admin')) {
                // Store Admin can only assign to their own store
                if (!$creatorUser->store_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Store admin does not have an assigned store.',
                    ], 400);
                }
                $storeIdForNewStaff = $creatorUser->store_id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only HR or Store Admins can register staff.',
                ], 403);
            }

            $generateUserId = User::generateUserId();

            $user = User::create([
                'user_id' => $generateUserId,
                'fname' => $validated['fname'],
                'lname' => $validated['lname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'store_id' => $storeIdForNewStaff, // Determined above
                'branch_id' => $validated['branch_id'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'registered_by' => $validated['current_user_id'],
            ]);

            // Check if request expects JSON (API) or web
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Staff registered successfully.',
                    'staff' => [
                        'user_id' => $user->user_id,
                        'firstname' => $user->fname,
                        'lastname' => $user->lname,
                        'email' => $user->email,
                        'role' => $user->role,
                        'store_id' => $user->store_id,
                        'branch_id' => $user->branch_id,
                        'registered_by' => $user->registered_by,
                        'employee_id_formatted' => $user->user_id,
                    ],
                ], 201);
            } else {
                return redirect()->route('staff.index')->with('success', 'Staff registered successfully.');
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to register staff',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function show(User $user)
    {
        try {
            $currentUser = auth()->user();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            // Everyone can view themselves
            if ($currentUser->id === $user->id) {
                $user->load(['role:id,name,display_name']);
                return response()->json([
                    'success' => true,
                    'data' => new UserResource($user),
                    'message' => 'User details retrieved successfully'
                ]);
            }

            // Check if user has permission to view others
            $allowedRoles = ['super_admin', 'store_admin', 'hr_manager'];
            if (!$currentUser->hasAnyRole($allowedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this user'
                ], 403);
            }

            // Load role relationship to check permissions
            $currentUser->load('role');

            // Super admin can view soft-deleted users
            if ($currentUser->hasRole('super_admin')) {
                $user = User::withTrashed()->findOrFail($user->id);
            }

            // Check specific permissions based on role
            if ($currentUser->hasRole('store_admin')) {
                // Store admin can only view users from same store
                if (!$currentUser->store_id || $currentUser->store_id !== $user->store_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot view users from other stores'
                    ], 403);
                }
            }

            if ($currentUser->hasRole('hr_manager')) {
                // HR manager can only view users from same store
                if (!$currentUser->store_id || $currentUser->store_id !== $user->store_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot view users from other stores'
                    ], 403);
                }
            }

            // Load relationships
            $user->load([
                'role:id,name,display_name',
                'store:id,store_name,store_code',
                'branch:id,name,branch_code'
            ]);

            return response()->json([
                'success' => true,
                'data' => new UserResource($user),
                'message' => 'User details retrieved successfully'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve user details', [
                'error' => $e->getMessage(),
                'user_id' => $user->id ?? 'unknown',
                'viewer_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user details',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }


    public function update(Request $request, User $user)
    {
        try {
            // Find user by primary key (id)
            $user = User::find($user);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id . ',id',
                'role' => 'sometimes|in:admin,manager,clerk,sales,customer',
                'store_id' => 'sometimes|nullable|exists:stores,store_id',
                'branch_id' => 'sometimes|nullable|exists:branches,branch_id',
                'phone_number' => 'sometimes|nullable|string|max:20',
                'status' => 'sometimes|in:active,inactive,suspended'
            ]);

            if ($request->has('password')) {
                $request->validate(['password' => 'string|min:6']);
                $validated['password'] = Hash::make($request->password);
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update($validated);

            // Then refresh the model
            $user->refresh();
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user'
            ], 500);
        }
    }
    public function destroy(Request $request, User $user)
    {
        try {
            // ONLY need the deleter's numeric ID from frontend
            $validated = $request->validate([
                'deleter_id' => 'required|exists:users,id', // Just the numeric ID
            ]);

            // Fetch FULL user object from database
            $deleter = User::find($validated['deleter_id']);

            // $user is already the user to delete (from route model binding)
            $userToDelete = $user; // Just assign it, no need to find again

            // Self deletion check
            if ($userToDelete->id === $deleter->id) {
                return response()->json(['success' => false, 'message' => 'Cannot delete yourself'], 400);
            }

            // Check permissions using ACTUAL database values
            if ($deleter->role === 'storeAdmin') {
                // Store admin can only delete users from their own store
                if ($deleter->store_id !== $userToDelete->store_id) {
                    return response()->json(['success' => false, 'message' => 'Can only delete from your store'], 403);
                }

                // Store admin cannot delete other admins
                if (in_array($userToDelete->role, ['storeAdmin', 'admin'])) {
                    return response()->json(['success' => false, 'message' => 'Cannot delete admin'], 403);
                }
            } elseif ($deleter->role === 'hr') {
                // HR cannot delete other HR or admins
                if (in_array($userToDelete->role, ['hr', 'admin'])) {
                    return response()->json(['success' => false, 'message' => 'Cannot delete HR/admin'], 403);
                }
            } elseif ($deleter->role === 'admin') {
                // Main admin cannot delete other main admins
                if ($userToDelete->role === 'admin') {
                    return response()->json(['success' => false, 'message' => 'Cannot delete other admin'], 403);
                }
            } else {
                // Other roles cannot delete
                return response()->json(['success' => false, 'message' => 'No permission'], 403);
            }

            // Soft delete
            $userToDelete->update([
                'status' => 'inactive',
                'deleted_by' => $deleter->id,
                'deleted_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User deactivated',
                'deleted_user' => $userToDelete->user_id,
                'deleted_by' => $deleter->user_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Error'
            ], 500);
        }
    }

    /**
     * Check if current user can view the target user
     */
}
