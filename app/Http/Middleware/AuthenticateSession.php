<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession as SanctumAuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession extends SanctumAuthenticateSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app(ImpersonateManager::class)->isImpersonating()) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
