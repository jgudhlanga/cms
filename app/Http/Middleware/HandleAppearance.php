<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'system';

        $systemPrefersDark = strcasecmp((string) $request->header('Sec-CH-Prefers-Color-Scheme', ''), 'dark') === 0;

        View::share('appearance', $appearance);
        View::share('systemPrefersDark', $systemPrefersDark);

        $response = $next($request);

        $response->headers->set('Accept-CH', 'Sec-CH-Prefers-Color-Scheme');
        $response->headers->set('Critical-CH', 'Sec-CH-Prefers-Color-Scheme');
        $response->headers->set('Vary', 'Sec-CH-Prefers-Color-Scheme');

        return $response;
    }
}
