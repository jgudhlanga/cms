<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\Level;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }
        // ❗ If this user is impersonated, skip student redirects
        if ($user->isImpersonated()) {
            return $next($request);
        }
        // ─────────────────────────────────────────────
        // ONLY FOR STUDENTS
        // ─────────────────────────────────────────────
        if ($user->hasRole(RoleEnum::STUDENT->name())) {
            // ─── STUDENT ALREADY HAS PROFILE ───
            if ($user->has_student_profile) {
                // Block them from application flow
                if ($request->routeIs(
                    'portal.application.fee-payment',
                    'portal.application.create',
                    'portal.application.confirm',
                    'portal.application.level-options'
                )) {
                    return to_route('portal.dashboard');
                }
                return $next($request);
            }
            // ─── STUDENT WITHOUT PROFILE ───
            $levelId = session('application.level_id');
            $level = $levelId ? Level::find($levelId) : null;
            // 1. No level chosen → force selection
            if (!$level) {
                if (!$request->routeIs(
                    'portal.application.level-options',
                    'portal.application.select-level',
                    'portal.application.fee-payment' // 👈 allow this
                )) {
                    return to_route('portal.application.level-options');
                }
                return $next($request);
            }
            // 2. Level requires fee
            if ($level->has_application_fee_payment) {
                if (PaymentHelper::hasPaidApplicationFeeAndNotApplied()) {

                    if (!$request->routeIs(
                        'portal.application.create',
                        'portal.application.confirm',
                    )) {
                        return to_route('portal.application.create');
                    }
                    return $next($request);
                }
                // Fee not paid
                if (!$request->routeIs('portal.application.fee-payment')) {
                    return to_route('portal.application.fee-payment');
                }
                return $next($request);
            }
            // 3. No fee required → go straight to create
            if (!$request->routeIs('portal.application.create') && !$request->routeIs('portal.application.confirm')) {
                return to_route('portal.application.create');
            }
            return $next($request);
        }
        // ─────────────────────────────────────────────
        // NON STUDENTS → leave them alone
        // ─────────────────────────────────────────────
        return $next($request);
    }
}

