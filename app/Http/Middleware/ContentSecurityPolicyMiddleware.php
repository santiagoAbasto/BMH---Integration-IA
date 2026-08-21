<?php

namespace App\Http\Middleware;

use Closure;

class ContentSecurityPolicyMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        //$response->headers->set('Content-Security-Policy', "script-src 'self' https://http2.mlstatic.com 'unsafe-inline' ");
        //$response->headers->remove('Content-Security-Policy');
        $response->headers->set('Content-Security-Policy', "script-src * https://http2.mlstatic.com 'unsafe-inline' 'unsafe-eval'");
        //$response->headers->set('Content-Security-Policy', "script-src 'self' https://http2.mlstatic.com 'unsafe-inline' 'unsafe-eval';");


        return $response;
    }
}