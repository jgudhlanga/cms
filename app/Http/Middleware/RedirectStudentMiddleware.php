<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Helpers\PaymentHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->hasRole(RoleEnum::STUDENT->name())) {
            if ($user->has_student_profile) {
                # Let them go anywhere except back to 'portal.application'
                $restrictedRoutes = ['portal.application.create', 'dashboard'];
                if ($request->routeIs(...$restrictedRoutes)) {
                    return to_route('portal.dashboard');
                }
            } else {
                if (PaymentHelper::hasPaidRegistrationFee()) {
                    if (!$request->routeIs(['portal.application.create', 'portal.application.confirm'])) {
                        return to_route('portal.application.create');
                    }
                } else {
                    # If no student profile, always go to application page unless already there
                    if (!$request->routeIs('portal.application.fee-payment')) {
                        return to_route('portal.application.fee-payment');
                    }
                }
            }
        } elseif ($user) {
            # Any other logged-in role, redirect to general dashboard if not already there
            if (!$request->routeIs('dashboard')) {
                return to_route('dashboard');
            }
        }
        return $next($request);
    }
}

