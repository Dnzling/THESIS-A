<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            abort(401, 'Unauthenticated');
        }
        
        $hasRole = method_exists($user, 'hasAnyRole')
            ? $user->hasAnyRole($roles)
            : (method_exists($user, 'hasRole') ? $user->hasRole($roles[0] ?? '') : false);

        if (!$hasRole) {
            $message = 'Unauthorized. Required roles: ' . implode(', ', $roles);
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }
            abort(403, $message);
        }
        
        return $next($request);
    }
}
