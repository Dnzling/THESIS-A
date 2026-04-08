<?php

namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $perPage = $request->get('per_page', 15);
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $onlyUnread = $request->boolean('unread_only', false);

            $query = InventoryNotification::where('user_id', $user->id)
                ->where('store_id', $user->store_id);

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
                    'unread_count' => InventoryNotification::where('user_id', $user->id)
                        ->where('store_id', $user->store_id)
                        ->where('is_read', false)
                        ->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a single notification
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $notification = InventoryNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

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

    /**
     * Mark a single notification as read
     *
     * @param string $id
     * @return JsonResponse
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $notification = InventoryNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

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

    /**
     * Mark all notifications as read for user
     *
     * @return JsonResponse
     */
    public function markAllAsRead(): JsonResponse
    {
        try {
            $user = auth()->user();
            $updated = InventoryNotification::where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->where('is_read', false)
                ->update([
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

    /**
     * Delete a single notification
     *
     * @param string $id
     * @return JsonResponse
     */
    public function delete(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $notification = InventoryNotification::where('id', $id)
                ->where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->firstOrFail();

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

    /**
     * Delete multiple notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
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

            $deleted = InventoryNotification::where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->whereIn('id', $ids)
                ->delete();

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

    /**
     * Get count of unread notifications
     *
     * @return JsonResponse
     */
    public function getUnread(): JsonResponse
    {
        try {
            $user = auth()->user();
            $unreadCount = InventoryNotification::where('user_id', $user->id)
                ->where('store_id', $user->store_id)
                ->where('is_read', false)
                ->count();

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
