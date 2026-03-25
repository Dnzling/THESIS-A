<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceChatMessage;
use App\Models\Ecommerce\EcommerceChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesChatController extends Controller
{
    public function threads(Request $request): JsonResponse
    {
        $user = $request->user();
        $search = trim((string) $request->input('search', ''));
        $perPage = max(1, min((int) $request->input('per_page', 20), 100));

        $query = EcommerceChatThread::query()
            ->with([
                'customer:id,fname,lname,email',
                'messages' => fn ($q) => $q->latest('created_at')->limit(1),
            ]);

        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', (int) $user->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        if ($search !== '') {
            $query->whereHas('customer', function ($customerQuery) use ($search): void {
                $customerQuery
                    ->where('fname', 'like', '%' . $search . '%')
                    ->orWhere('lname', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        $threads = $query
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        $threads->getCollection()->transform(function (EcommerceChatThread $thread) {
            $lastMessage = $thread->messages->first();
            $unreadCount = EcommerceChatMessage::query()
                ->where('thread_id', $thread->id)
                ->where('sender_role', 'customer')
                ->whereNull('read_at')
                ->count();

            return [
                'id' => $thread->id,
                'store_id' => $thread->store_id,
                'customer_user_id' => $thread->customer_user_id,
                'customer_name' => trim((string) (($thread->customer?->fname ?? '') . ' ' . ($thread->customer?->lname ?? ''))) ?: 'Customer',
                'customer_email' => $thread->customer?->email,
                'last_message' => $lastMessage?->message,
                'last_message_at' => $thread->last_message_at ?? $lastMessage?->created_at,
                'unread_count' => $unreadCount,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $threads,
        ]);
    }

    public function messages(Request $request, int $threadId): JsonResponse
    {
        $thread = $this->resolveThread($request, $threadId);
        $perPage = max(1, min((int) $request->input('per_page', 50), 200));

        $messages = EcommerceChatMessage::query()
            ->with('sender:id,fname,lname,email')
            ->where('thread_id', $thread->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        EcommerceChatMessage::query()
            ->where('thread_id', $thread->id)
            ->where('sender_role', 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'data' => $messages,
            'thread' => [
                'id' => $thread->id,
                'customer_user_id' => $thread->customer_user_id,
            ],
        ]);
    }

    public function sendMessage(Request $request, int $threadId): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'order_id' => ['nullable', 'integer', 'exists:ecommerce_orders,id'],
        ]);

        $thread = $this->resolveThread($request, $threadId);
        $user = $request->user();

        $message = EcommerceChatMessage::query()->create([
            'thread_id' => $thread->id,
            'sender_user_id' => $user->id,
            'sender_role' => 'store',
            'message' => trim((string) $validated['message']),
            'order_id' => $validated['order_id'] ?? null,
        ]);

        $thread->update(['last_message_at' => $message->created_at]);

        return response()->json([
            'success' => true,
            'data' => $message,
        ], 201);
    }

    private function resolveThread(Request $request, int $threadId): EcommerceChatThread
    {
        $query = EcommerceChatThread::query()->where('id', $threadId);
        $user = $request->user();

        if (!$user->hasRole('super_admin')) {
            $query->where('store_id', (int) $user->store_id);
        } elseif ($request->filled('store_id')) {
            $query->where('store_id', (int) $request->input('store_id'));
        }

        return $query->firstOrFail();
    }
}

