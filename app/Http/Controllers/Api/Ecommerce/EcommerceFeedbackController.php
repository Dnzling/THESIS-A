<?php

namespace App\Http\Controllers\Api\Ecommerce;

use App\Http\Controllers\Controller;
use App\Models\Ecommerce\EcommerceFeedback;
use Illuminate\Http\Request;

class EcommerceFeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'topic' => ['required', 'in:website,products,checkout,delivery,other'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'message' => ['nullable', 'string', 'max:2000'],
            'contact_allowed' => ['boolean'],
            'page_url' => ['nullable', 'string', 'max:500'],
        ]);
        if (empty($data['rating']) && blank($data['message'] ?? null)) {
            return response()->json(['message' => 'Please select a rating or leave a comment.'], 422);
        }
        EcommerceFeedback::create([...$data, 'user_id' => optional($request->user())->id]);
        return response()->json(['success' => true, 'message' => 'Thank you for your feedback.']);
    }

    public function index(Request $request)
    {
        $query = EcommerceFeedback::query()->with(['user:id,fname,lname,email', 'handler:id,fname,lname'])->latest();
        if ($request->filled('status')) $query->where('status', (string) $request->input('status'));
        if ($request->filled('topic')) $query->where('topic', (string) $request->input('topic'));
        if ($request->filled('rating')) $query->where('rating', (int) $request->input('rating'));
        if ($search = trim((string) $request->input('search'))) $query->where(fn ($q) => $q->where('message', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$search}%")));
        return response()->json(['success' => true, 'data' => $query->paginate(min(100, max(10, (int) $request->input('per_page', 20))))]);
    }

    public function update(Request $request, EcommerceFeedback $feedback)
    {
        $data = $request->validate(['status' => ['required', 'in:new,in_review,resolved,archived'], 'admin_note' => ['nullable', 'string', 'max:2000']]);
        $data['handled_by'] = $request->user()->id;
        $data['handled_at'] = now();
        $feedback->update($data);
        return response()->json(['success' => true, 'data' => $feedback->fresh('handler')]);
    }
}
