<?php

declare(strict_types = 1);

namespace Bidb97\QueryExplain\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * QueryExplain Authorization Middleware
 *
 * Handles authorization for the QueryExplain interface.
 * Controls access to the query explanation functionality.
 *
 */
class Authorize
{
    /**
     * Handle an incoming request.
     *
     * Processes the incoming HTTP request and determines whether the user
     * is authorized to access the QueryExplain functionality.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next  The next middleware in the stack
     * @return \Symfony\Component\HttpFoundation\Response  The HTTP response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Implement proper authorization logic
        // For now, allow all requests in development
        // In production, this should check user permissions, roles, or other authorization criteria

        return $next($request);
    }
}
