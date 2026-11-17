<?php

namespace App\Http\Middleware;

use App\Services\Core\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAnyPermission
{
    public function __construct(private readonly PermissionService $permissions)
    {
    }

    public function handle(Request $request, Closure $next, string ...$permissionNames): Response
    {
        $user = $request->user();
        abort_unless($user && collect($permissionNames)->contains(
            fn (string $permission): bool => $this->permissions->userHasPermission($user, $permission),
        ), 403, 'You do not have permission to access this logistics resource.');

        return $next($request);
    }
}
