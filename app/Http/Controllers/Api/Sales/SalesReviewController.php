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
            ->with(['product:id,product_name,sku', 'replier:id,fname,lname'])
            ->where('store_id', $storeId)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->integer('rating'));
        }

        if ($request->filled('search')) {
            $term = $request->string('search');
            $query->where(function ($q) use ($term) {
                $q->where('customer_name', 'like', "%{$term}%")
                    ->orWhere('order_id', 'like', "%{$term}%")
                    ->orWhere('message', 'like', "%{$term}%");
            });
        }

        $perPage = max(10, min((int) $request->integer('per_page', 20), 100));
        $reviews = $query->paginate($perPage);

        $base = SalesReview::query()->where('store_id', $storeId);
        $summary = [
            'total_reviews' => (int) (clone $base)->count(),
            'pending_reviews' => (int) (clone $base)->where('status', 'pending')->count(),
            'replied_reviews' => (int) (clone $base)->where('status', 'replied')->count(),
            'average_rating' => round((float) ((clone $base)->avg('rating') ?? 0), 2),
            'ratings_breakdown' => [
                5 => (int) (clone $base)->where('rating', 5)->count(),
                4 => (int) (clone $base)->where('rating', 4)->count(),
                3 => (int) (clone $base)->where('rating', 3)->count(),
                2 => (int) (clone $base)->where('rating', 2)->count(),
                1 => (int) (clone $base)->where('rating', 1)->count(),
            ],
        ];

        return response()->json([
            'data' => $reviews,
            'summary' => $summary,
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
