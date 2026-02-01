<?php

namespace Bidb97\QueryExplain\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * QueryExplain Authorization Middleware
 *
 * Handles authorization for the QueryExplain interface.
 * Currently allows all requests, but can be customized to restrict access.
 */
class Authorize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // TODO: Implement proper authorization logic
        // For now, allow all requests in development

        return $next($request);
    }
}
