<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent stateful Sanctum API GETs from replacing the session previous URL.
 *
 * Axios SPA calls hit /api/* with the web session but without X-Requested-With,
 * so StartSession stores the API URL. Later back() redirects then show raw JSON.
 */
class RestorePreviousUrlAfterApiRequest
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->hasSession()) {
            return $response;
        }

        if (! $request->is('api') && ! $request->is('api/*')) {
            return $response;
        }

        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return $response;
        }

        $path = parse_url($referer, PHP_URL_PATH) ?? '';
        if ($path === '/api' || str_starts_with($path, '/api/')) {
            return $response;
        }

        $origin = $request->getSchemeAndHttpHost();
        if (! str_starts_with($referer, $origin)) {
            return $response;
        }

        $request->session()->setPreviousUrl($referer);

        return $response;
    }
}
