<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\SystemNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = (int) $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $onlyUnread = $request->boolean('unread_only', false);

            $query = SystemNotification::where('user_id', $user->id);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }

            if ($request->filled('module')) {
                $query->where('module', $request->module);
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }

            if ($request->filled('entity_type')) {
                $query->where('entity_type', $request->entity_type);
            }

            if ($onlyUnread) {
                $query->where('is_read', false);
            }

            $notifications = $query
                ->orderBy($sortBy, $sortOrder)
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $notifications->items(),
                'meta' => [
                    'total' => $notifications->total(),
                    'count' => count($notifications->items()),
                    'per_page' => $notifications->perPage(),
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'unread_count' => (function () use ($user) {
                        $q = SystemNotification::where('user_id', $user->id)->where('is_read', false);
                        if (!is_null($user->store_id)) {
                            $q->where('store_id', $user->store_id);
                        }
                        return $q->count();
                    })(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = SystemNotification::where('id', $id)->where('user_id', $user->id);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $notification = $query->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $notification,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = SystemNotification::where('id', $id)->where('user_id', $user->id);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $notification = $query->firstOrFail();

            $notification->markAsRead();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => $notification,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = SystemNotification::where('user_id', $user->id)->where('is_read', false);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $updated = $query->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);

            return response()->json([
                'success' => true,
                'message' => "Marked {$updated} notifications as read",
                'meta' => ['updated_count' => $updated],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function delete(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = SystemNotification::where('id', $id)->where('user_id', $user->id);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $notification = $query->firstOrFail();

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function batchDelete(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $ids = $request->get('ids', []);

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No notification IDs provided',
                ], 422);
            }

            $query = SystemNotification::where('user_id', $user->id)->whereIn('id', $ids);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $deleted = $query->delete();

            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} notifications",
                'meta' => ['deleted_count' => $deleted],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getUnread(): JsonResponse
    {
        try {
            $user = auth()->user();
            $query = SystemNotification::where('user_id', $user->id)->where('is_read', false);
            if (!is_null($user->store_id)) {
                $query->where('store_id', $user->store_id);
            }
            $unreadCount = $query->count();

            return response()->json([
                'success' => true,
                'data' => ['unread_count' => $unreadCount],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve unread count: ' . $e->getMessage(),
            ], 500);
        }
    }
}
