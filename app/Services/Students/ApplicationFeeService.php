<?php

namespace App\Services\Students;

use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\Helper;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Students\ApplicationFee;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ApplicationFeeService
{
    public function resolveIntakePeriod(?int $intakePeriodId = null): IntakePeriod
    {
        return $this->resolvePortalIntakePeriod($intakePeriodId);
    }

    public function resolvePortalIntakePeriod(?int $intakePeriodId = null): IntakePeriod
    {
        if ($intakePeriodId !== null && $intakePeriodId > 0) {
            return IntakePeriod::query()
                ->where('is_active', true)
                ->findOrFail($intakePeriodId);
        }

        return IntakePeriod::query()
            ->where('is_active', true)
            ->orderByDesc('end_date')
            ->firstOrFail();
    }

    /**
     * @return Collection<int, IntakePeriod>
     */
    public function openIntakePeriodsForPortal(): Collection
    {
        return IntakePeriod::query()
            ->where('is_active', true)
            ->orderByDesc('end_date')
            ->get();
    }

    public function latestForUser(User $user): ?ApplicationFee
    {
        return ApplicationFee::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();
    }

    public function activeApplicationFee(User $user): ?ApplicationFee
    {
        return ApplicationFee::query()
            ->where('user_id', $user->id)
            ->whereNull('student_application_id')
            ->whereNot('status', ApplicationFeeStatusEnum::CANCELLED)
            ->latest('id')
            ->first();
    }

    public function abandonUnpaidApplicationFee(User $user): void
    {
        $applicationFee = $this->activeApplicationFee($user);

        if ($applicationFee === null || ! $applicationFee->isAwaitingPayment()) {
            return;
        }

        $applicationFee->update([
            'status' => ApplicationFeeStatusEnum::CANCELLED,
        ]);
    }

    public function paidAwaitingApplication(User $user): ?ApplicationFee
    {
        $applicationFee = $this->activeApplicationFee($user);

        if ($applicationFee === null || ! $applicationFee->isPaid()) {
            return null;
        }

        return $applicationFee;
    }

    public function resolveIntakeForSubmit(User $user, ?int $requestIntakePeriodId = null): IntakePeriod
    {
        $applicationFee = $this->activeApplicationFee($user);

        if ($applicationFee !== null) {
            return $applicationFee->intakePeriod;
        }

        return $this->resolvePortalIntakePeriod($requestIntakePeriodId);
    }

    public function forUserAndIntake(User $user, ?IntakePeriod $intakePeriod = null): ?ApplicationFee
    {
        $intakePeriod ??= Helper::resolveIntakePeriod();

        return ApplicationFee::query()
            ->where('user_id', $user->id)
            ->where('intake_period_id', $intakePeriod->id)
            ->first();
    }

    public function ensureForFeeRequiredLevel(User $user, Level $level, ?IntakePeriod $intakePeriod = null): ApplicationFee
    {
        if (! $level->has_application_fee_payment) {
            throw new \InvalidArgumentException('Level does not require an application fee.');
        }

        $intakePeriod ??= $this->resolvePortalIntakePeriod();

        $applicationFee = ApplicationFee::query()->firstOrNew([
            'user_id' => $user->id,
            'intake_period_id' => $intakePeriod->id,
        ]);

        $attributes = [
            'tenant_id' => $user->tenant_id,
            'level_id' => $level->id,
        ];

        if (session()->has('registration.id_type_id')) {
            $attributes['id_type_id'] = session('registration.id_type_id');
        }

        if (session('registration.id_number')) {
            $attributes['id_number'] = session('registration.id_number');
        }

        if (session('registration.passport_number')) {
            $attributes['passport_number'] = session('registration.passport_number');
        }

        if (
            ! $applicationFee->exists
            || $applicationFee->status === ApplicationFeeStatusEnum::AWAITING_PAYMENT
            || $applicationFee->status === ApplicationFeeStatusEnum::CANCELLED
        ) {
            $attributes['status'] = ApplicationFeeStatusEnum::AWAITING_PAYMENT;
        }

        $applicationFee->fill($attributes);
        $applicationFee->save();

        return $applicationFee->fresh();
    }

    public function markSubmitted(ApplicationFee $applicationFee, StudentApplication $studentApplication): void
    {
        $applicationFee->update([
            'status' => ApplicationFeeStatusEnum::SUBMITTED,
            'student_application_id' => $studentApplication->id,
            'level_id' => $studentApplication->departmentLevel->level_id,
        ]);
    }

    public function unpaidForCurrentIntake(User $user): ?ApplicationFee
    {
        $applicationFee = $this->activeApplicationFee($user);

        if ($applicationFee === null || $applicationFee->isPaid()) {
            return null;
        }

        return $applicationFee;
    }

    public function assertPortalIntakePeriod(?int $intakePeriodId): IntakePeriod
    {
        if ($intakePeriodId === null || $intakePeriodId <= 0) {
            throw ValidationException::withMessages([
                'intake_period_id' => [__('trans.portal_intake_period_required')],
            ]);
        }

        $intakePeriod = IntakePeriod::query()
            ->where('is_active', true)
            ->find($intakePeriodId);

        if ($intakePeriod === null) {
            throw ValidationException::withMessages([
                'intake_period_id' => [__('trans.portal_intake_period_invalid')],
            ]);
        }

        return $intakePeriod;
    }
}
