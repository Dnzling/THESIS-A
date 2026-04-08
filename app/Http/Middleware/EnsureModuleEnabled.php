<?php

namespace App\Http\Middleware;

use App\Services\Modules\ModuleAccessService;
use Closure;
use Illuminate\Http\Request;

class EnsureModuleEnabled
{
    public function __construct(private ModuleAccessService $modules)
    {
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $moduleKey
     */
    public function handle(Request $request, Closure $next, string $moduleKey)
    {
        // Temporarily bypass module gating
        return $next($request);
    }
}
