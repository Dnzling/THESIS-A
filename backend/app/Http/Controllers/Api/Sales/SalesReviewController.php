<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesReview;
use Illuminate\Http\Request;

class SalesReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $storeId = $user?->store_id;

        if (!$storeId) {
            return response()->json(['data' => []]);
        }

        $query = SalesReview::query()
            ->where('store_id', $storeId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('order_id', 'like', "%{$term}%")
                    ->orWhere('message', 'like', "%{$term}%");
            });
        }

        return response()->json([
            'data' => $query->paginate(20),
        ]);
    }

    public function show(SalesReview $review)
    {
        return response()->json([
            'data' => $review,
        ]);
    }

    public function reply(Request $request, SalesReview $review)
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:2000'],
        ]);

        $user = $request->user();

        $review->update([
            'reply' => $request->string('reply'),
            'replied_by' => $user?->id,
            'replied_at' => now(),
            'status' => 'replied',
        ]);

        return response()->json([
            'message' => 'Reply saved.',
            'data' => $review,
        ]);
    }
}
