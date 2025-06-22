<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
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
        }

        return $next($request);
    }
}
