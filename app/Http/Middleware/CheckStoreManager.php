<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\StoreModel;

class CheckStoreManager
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $storeId = $request->route('store');
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Admin can access any store
        if ($user->role === 'admin') {
            return $next($request);
        }
        
        // Manager can only access their assigned store
        if ($user->role === 'manager' && $user->store_id == $storeId) {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'Unauthorized to access this store'
        ], 403);
    }
}