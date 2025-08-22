<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Get security headers from config
        $securityHeaders = config('api.security_headers', []);

        // Add security headers to response
        foreach ($securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        // Add custom headers for API
        $response->headers->set('X-API-Version', '1.0');
        $response->headers->set('X-Request-ID', $request->header('X-Request-ID') ?? uniqid());

        return $response;
    }
} 