<?php

namespace App\Http\Middleware;

use App\Enums\Shared\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->hasRole(RoleEnum::STUDENT)) {
            if ($user->has_student_profile && !$request->routeIs('portal.dashboard')) {
                return to_route('portal.dashboard');
            }

            if (!$user->has_student_profile && !$request->routeIs('portal.application')) {
                return to_route('portal.application');
            }
        } else {
            // Not a student, redirect to the general dashboard
            if (!$request->routeIs('dashboard')) {
                return to_route('dashboard');
            }
        }

        return $next($request);
    }
}
