<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Symfony\Component\HttpFoundation\Response;

class ConditionalStatefulMiddleware
{
    /**
     * Handle an incoming request.
     * Only apply stateful behavior for non-API routes (like login/register).
     * API routes use token-based auth and don't need CSRF protection.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip stateful middleware for API routes (they use token auth)
        $path = $request->path();
        $uri = $request->getRequestUri();
        
        // Check if this is an API route by checking path or URI
        if (strpos($path, 'api/') === 0 || strpos($uri, '/api/') !== false) {
            return $next($request);
        }

        // Apply stateful middleware for web routes (login, register, etc.)
        $statefulMiddleware = new EnsureFrontendRequestsAreStateful();
        return $statefulMiddleware->handle($request, $next);
    }
}

