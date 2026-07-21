<?php

namespace App\Http\Middleware;

use App\Enums\Acl\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\PaymentHelper;
use App\Models\Institution\Level;
use App\Models\Students\ApplicationFee;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\ReturningStudentContextService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStudentMiddleware
{
    public function __construct(
        protected ApplicationFeeService $applicationFeeService,
        protected ReturningStudentContextService $returningStudentContext,
        protected ApplicationTrackSession $trackSession,
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
            return $this->handleReturningStudentProfile($request, $next, $user);
        }

        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);

        if ($applicationFee?->status === ApplicationFeeStatusEnum::SUBMITTED) {
            if (! $request->routeIs('portal.applications', 'portal.application.view', 'portal.profile.applications')) {
                return to_route('portal.applications');
            }

            return $next($request);
        }

        if ($this->trackSession->get() === null) {
            if (! $request->routeIs('portal.application.track', 'portal.application.select-track')) {
                return to_route('portal.application.track');
            }

            return $next($request);
        }

        $track = $this->trackSession->get();
        if ($track === ApplicationTrackEnum::Apprentice) {
            if (! $request->routeIs(
                'portal.application.track',
                'portal.application.select-track',
                'portal.application.apprentice',
                'portal.application.apprentice.store',
            )) {
                return to_route('portal.application.apprentice');
            }

            return $next($request);
        }

        $level = $this->resolveLevel($applicationFee, $user);

        if ($level === null) {
            if (! $request->routeIs(
                'portal.application.track',
                'portal.application.select-track',
                'portal.application.level-options',
                'portal.application.select-level',
                'portal.application.confirm',
                'portal.application.create',
                'portal.application.fee-payment',
                'portal.store-application',
            )) {
                return to_route('portal.application.level-options');
            }

            return $next($request);
        }

        if (PaymentHelper::levelRequiresApplicationFeePayment($level, $user)) {
            if ($applicationFee === null) {
                if (! $request->routeIs(
                    'portal.application.track',
                    'portal.application.select-track',
                    'portal.application.level-options',
                    'portal.application.select-level',
                )) {
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

            if (! $request->routeIs(
                'portal.application.fee-payment',
                'portal.application.level-options',
                'portal.application.select-level',
                'portal.application.track',
                'portal.application.select-track',
            )) {
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

    private function handleReturningStudentProfile(Request $request, Closure $next, $user): Response
    {
        if ($request->routeIs(
            'portal.application.create',
            'portal.application.confirm',
            'portal.application.level-options',
            'portal.application.track',
            'portal.store-application',
        )) {
            return to_route('portal.dashboard');
        }

        $student = $user->studentProfile;

        if ($student !== null && $this->returningStudentContext->needsContinueInClassPage($student)) {
            if (! $request->routeIs(
                'portal.returning-student.continue.*',
                'logout',
                'portal.profile.*',
                'portal.application.view',
                'portal.applications',
            )) {
                return to_route('portal.returning-student.continue.show');
            }
        }

        if ($request->routeIs('portal.application.returning*')) {
            $applicationFee = $this->applicationFeeService->activeApplicationFee($user);
            $intakePeriod = $applicationFee?->intakePeriod ?? $this->returningStudentContext->openIntakes()->first();

            if (
                $student !== null
                && $intakePeriod !== null
                && $this->returningStudentContext->hasReapplyAcknowledgementForIntake($student, $intakePeriod)
            ) {
                $level = $applicationFee?->level ?? Level::query()->find(session('application.level_id'));

                if (
                    $level !== null
                    && PaymentHelper::levelRequiresApplicationFeePayment($level, $user)
                    && ! PaymentHelper::hasPaidApplicationFeeAndNotApplied($user, $intakePeriod)
                ) {
                    return to_route('portal.application.fee-payment');
                }

                return $next($request);
            }

            return to_route('portal.profile.applications');
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
