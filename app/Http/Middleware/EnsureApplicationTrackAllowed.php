<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\Students\ApplicationTrackEnum;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationAvailabilityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the session application track is open; redirects to track chooser or maintenance.
 */
class EnsureApplicationTrackAllowed
{
    public function __construct(
        protected RegistrationAvailabilityService $registrationAvailability,
        protected ApplicationTrackSession $trackSession,
        protected ApplicationEligibilityService $eligibility,
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->registrationAvailability->isAnyRegistrationOpen()) {
            return to_route('portal.registration.maintenance');
        }

        if ($request->routeIs('portal.application.track', 'portal.application.select-track')) {
            return $next($request);
        }

        if ($request->routeIs('portal.application.apprentice', 'portal.application.apprentice.store')) {
            $track = $this->trackSession->get();
            if ($track === ApplicationTrackEnum::Apprentice) {
                return $next($request);
            }
        }

        $track = $this->trackSession->get() ?? $this->inferTrackFromApplicationFee($request);

        if ($track === null) {
            return to_route('portal.application.track');
        }

        if (! $this->registrationAvailability->isTrackOpen($track)) {
            $this->trackSession->clear();

            return to_route('portal.application.track');
        }

        return $next($request);
    }

    private function inferTrackFromApplicationFee(Request $request): ?ApplicationTrackEnum
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $fee = $this->applicationFeeService->activeApplicationFee($user);
        $intake = $fee?->intakePeriod;

        if ($intake === null) {
            return null;
        }

        return $intake->is_continuous
            ? ApplicationTrackEnum::Continuous
            : ApplicationTrackEnum::Regular;
    }
}
