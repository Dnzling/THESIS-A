<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSelfOrRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        $targetUserId = $request->route('user');
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Allow if user is accessing their own profile
        if ($user->user_id == $targetUserId) {
            return $next($request);
        }
        
        // Or if user has required role
        if (in_array($user->role, $roles)) {
            return $next($request);
        }
        
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }
}