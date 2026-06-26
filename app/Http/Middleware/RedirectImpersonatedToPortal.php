<?php

namespace App\Http\Middleware;

use App\Models\Users\User;
use App\Services\Auth\ImpersonationLandingResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectImpersonatedToPortal
{
    public function __construct(
        protected ImpersonationLandingResolver $landingResolver,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isImpersonated()) {
            return $next($request);
        }

        if (! $this->landingResolver->isStudentPortalUser($user)) {
            return $next($request);
        }

        if ($request->routeIs('portal.*', 'impersonate', 'impersonate.leave', 'logout')) {
            return $next($request);
        }

        return redirect()->to($this->landingResolver->landingUrl($user));
    }
}
