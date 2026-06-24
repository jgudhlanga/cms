<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\Level;
use App\Models\Students\ApplicationFee;
use App\Services\Students\ApplicationFeeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentMiddleware
{
    public function __construct(
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($user->isImpersonated()) {
            return $next($request);
        }

        if (! $user->hasRole(RoleEnum::STUDENT->name())) {
            return $next($request);
        }

        if ($user->has_student_profile) {
            if ($request->routeIs(
                'portal.application.fee-payment',
                'portal.application.create',
                'portal.application.confirm',
                'portal.application.level-options',
                'portal.store-application',
            )) {
                return to_route('portal.dashboard');
            }

            return $next($request);
        }

        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
        $level = $this->resolveLevel($applicationFee, $user);

        if ($level === null) {
            if (! $request->routeIs(
                'portal.application.level-options',
                'portal.application.select-level',
                'portal.application.confirm',
                'portal.application.create',
                'portal.application.fee-payment',
            )) {
                return to_route('portal.application.level-options');
            }

            return $next($request);
        }

        if ($applicationFee?->status === ApplicationFeeStatusEnum::SUBMITTED) {
            if (! $request->routeIs('portal.applications', 'portal.application.view')) {
                return to_route('portal.applications');
            }

            return $next($request);
        }

        if ($level->has_application_fee_payment) {
            if ($applicationFee === null) {
                if (! $request->routeIs('portal.application.level-options', 'portal.application.select-level')) {
                    return to_route('portal.application.level-options');
                }

                return $next($request);
            }

            $intakePeriod = $applicationFee->intakePeriod;

            if (PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)) {
                if (! $request->routeIs(
                    'portal.application.create',
                    'portal.application.confirm',
                    'portal.store-application',
                )) {
                    return to_route('portal.application.create');
                }

                return $next($request);
            }

            if (! $request->routeIs('portal.application.fee-payment')) {
                return to_route('portal.application.fee-payment');
            }

            return $next($request);
        }

        if (! $request->routeIs(
            'portal.application.create',
            'portal.application.confirm',
            'portal.store-application',
        )) {
            return to_route('portal.application.create');
        }

        return $next($request);
    }

    private function resolveLevel(?ApplicationFee $applicationFee, $user): ?Level
    {
        if ($applicationFee?->level !== null) {
            return $applicationFee->level;
        }

        $levelId = session('application.level_id');

        return $levelId ? Level::find($levelId) : null;
    }
}
