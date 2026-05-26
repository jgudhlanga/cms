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

        // Client hints may be absent until configured; avoids wrong class only when cookie is explicit.
        $systemPrefersDark = strcasecmp((string) $request->header('Sec-CH-Prefers-Color-Scheme', ''), 'dark') === 0;

        $htmlIsDark = match ($appearance) {
            'dark' => true,
            'light' => false,
            default => $systemPrefersDark,
        };

        View::share('appearance', $appearance);
        View::share('htmlIsDark', $htmlIsDark);

        return $next($request);
    }
}
