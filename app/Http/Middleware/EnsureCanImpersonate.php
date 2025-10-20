<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanImpersonate
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If you're can impersonate, and you're not impersonated already, block access
        if (!$request->user()->can_impersonate && !session('impersonated_by')) {
            abort(403, 'This action is unauthorized.');
        }
        return $next($request);
    }
}
