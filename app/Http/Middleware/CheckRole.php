<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // Requested behavior: remove RBAC for "view" endpoints across the API.
        // Treat GET/HEAD as view-only and allow them through for `/api/*` routes.
        if (($request->isMethod('GET') || $request->isMethod('HEAD')) && $request->is('api/*')) {
            return $next($request);
        }

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
