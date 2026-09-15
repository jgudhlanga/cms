<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser hardening headers for every response. A header already set by a controller wins.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->setDefault($response, 'X-Content-Type-Options', 'nosniff');
        $this->setDefault($response, 'Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->setDefault($response, 'X-Frame-Options', 'SAMEORIGIN');

        if (app()->isProduction() && $request->isSecure()) {
            $this->setDefault($response, 'Strict-Transport-Security', 'max-age=31536000');
        }

        if (config('custom.security.csp_report_only')) {
            $this->setDefault($response, 'Content-Security-Policy-Report-Only', (string) config('custom.security.csp_policy'));
        }

        return $response;
    }

    private function setDefault(Response $response, string $name, string $value): void
    {
        if (! $response->headers->has($name)) {
            $response->headers->set($name, $value);
        }
    }
}
