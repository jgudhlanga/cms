<?php

namespace App\Services\Auth;

use App\Enums\Acl\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Institution\Level;
use App\Models\Users\User;
use App\Services\Students\ApplicationFeeService;

class ImpersonationLandingResolver
{
    public function __construct(
        protected ApplicationFeeService $applicationFeeService,
    ) {}

    public function isStudentPortalUser(User $user): bool
    {
        return $user->hasRole(RoleEnum::STUDENT->name());
    }

    public function landingUrl(User $user): string
    {
        if ($this->isStudentPortalUser($user)) {
            return $this->studentLandingUrl($user);
        }

        return route('dashboard');
    }

    private function studentLandingUrl(User $user): string
    {
        if ($user->has_student_profile) {
            return route('portal.dashboard');
        }

        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);

        if ($applicationFee !== null) {
            if ($applicationFee->status === ApplicationFeeStatusEnum::SUBMITTED) {
                return route('portal.applications');
            }

            if ($applicationFee->isPaid()) {
                return route('portal.application.create');
            }

            if ($applicationFee->isAwaitingPayment()) {
                return route('portal.application.fee-payment');
            }
        }

        $levelId = session('application.level_id');
        $level = $levelId ? Level::find($levelId) : null;

        if ($level !== null && ! $level->has_application_fee_payment) {
            return route('portal.application.create');
        }

        if ($this->applicationFeeService->unpaidForCurrentIntake($user) !== null) {
            return route('portal.application.fee-payment');
        }

        return route('portal.application.level-options');
    }
}
