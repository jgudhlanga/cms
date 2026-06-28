<?php

namespace App\Http\Middleware;

use App\Services\Students\RegistrationAvailabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRegistrationOpen
{
    public function __construct(
        protected RegistrationAvailabilityService $registrationAvailability,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->registrationAvailability->isRegistrationOpen()) {
            return $next($request);
        }

        if ($request->routeIs('portal.registration.maintenance')) {
            return $next($request);
        }

        return to_route('portal.registration.maintenance');
    }
}
