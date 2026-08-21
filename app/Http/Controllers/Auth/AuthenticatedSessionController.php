<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\OjetFormerStudentIntentService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\RegistrationIntentSession;
use App\Services\Students\ReturningStudentContextService;
use App\Support\Auth\DefaultHome;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(
        LoginRequest $request,
        ApplicationFeeService $applicationFeeService,
        RegistrationAvailabilityService $registrationAvailability,
        ReturningStudentContextService $returningStudentContext,
        OjetFormerStudentIntentService $ojetFormerStudentIntent,
        RegistrationIntentSession $intentSession,
        ApplicationTrackSession $trackSession,
        ApplicationEligibilityService $eligibility,
    ): RedirectResponse {
        $request->authenticate();
        $request->session()->regenerate();
        $user = request()->user();
        if ($user->hasRole(RoleEnum::STUDENT->name())) {
            if ($user->has_student_profile) {
                $student = $user->studentProfile;

                if (
                    $student !== null
                    && $ojetFormerStudentIntent->hasPendingProgrammeIntent()
                    && $ojetFormerStudentIntent->studentMatchesVerifiedIdentity($student)
                ) {
                    $ojetFormerStudentIntent->promotePendingIntent();
                    $ojetFormerStudentIntent->ensureStudentNumber($student);

                    $registrationSession = [
                        'registration.path' => $intentSession->ojetIdentityType()
                            ?? session('application.ojet_identity_type'),
                        'registration.id_type_id' => ($intentSession->ojetIdentityType()
                            ?? session('application.ojet_identity_type')) === 'international'
                            ? IdTypeEnum::FOREIGN_PASSPORT_NUMBER->id()
                            : IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
                    ];

                    if ($intentSession->ojetIdNumber() ?? session('application.ojet_id_number')) {
                        $registrationSession['registration.id_number'] = $intentSession->ojetIdNumber()
                            ?? session('application.ojet_id_number');
                    }

                    if ($intentSession->ojetPassportNumber() ?? session('application.ojet_passport_number')) {
                        $registrationSession['registration.passport_number'] = $intentSession->ojetPassportNumber()
                            ?? session('application.ojet_passport_number');
                    }

                    if ($intentSession->ojetStudentNumber() ?? session('application.ojet_student_number')) {
                        $registrationSession['registration.ojet_student_number'] = $intentSession->ojetStudentNumber()
                            ?? session('application.ojet_student_number');
                    }

                    session($registrationSession);
                    $intentSession->clear();

                    $track = $trackSession->get();
                    $levelId = $trackSession->levelId() ?? session('application.level_id');
                    $level = $levelId !== null ? Level::query()->find($levelId) : null;

                    if (
                        $track instanceof ApplicationTrackEnum
                        && $level !== null
                        && $track !== ApplicationTrackEnum::Apprentice
                        && $eligibility->trackRequiresApplicationFee($track, $level, $user)
                    ) {
                        $intakePeriodId = $trackSession->intakePeriodId();
                        $intakePeriod = $intakePeriodId !== null
                            ? IntakePeriod::query()->findOrFail($intakePeriodId)
                            : $eligibility->resolveIntakeForTrack($track, null);
                        $applicationFeeService->ensureForFeeRequiredLevel($user, $level, $intakePeriod);

                        return to_route('portal.application.fee-payment');
                    }

                    $applicationFeeService->abandonUnpaidApplicationFee($user);

                    return to_route('portal.application.create');
                }

                if ($student !== null && $returningStudentContext->needsContinueInClassPage($student)) {
                    return to_route('portal.returning-student.continue.show');
                }
                if ($student !== null && $returningStudentContext->canStartApplication($student)) {
                    return to_route('portal.profile.applications');
                }

                return to_route('portal.dashboard');
            }

            if (! $registrationAvailability->isAnyRegistrationOpen()) {
                return to_route('portal.registration.maintenance');
            }

            $applicationFee = $applicationFeeService->activeApplicationFee($user);

            if ($applicationFee !== null) {
                if ($applicationFee->status === ApplicationFeeStatusEnum::SUBMITTED) {
                    return to_route('portal.applications');
                }

                if (PaymentHelper::isApplicationFeeExempt($user)) {
                    $applicationFeeService->abandonUnpaidApplicationFee($user);
                    session(['application.level_id' => $applicationFee->level_id]);

                    return to_route('portal.application.create');
                }

                if ($applicationFee->isPaid()) {
                    return to_route('portal.application.create');
                }

                if ($applicationFee->isAwaitingPayment()) {
                    return to_route('portal.application.fee-payment');
                }
            }

            $levelId = session('application.level_id');
            $level = $levelId ? Level::find($levelId) : null;

            if ($level !== null && ! PaymentHelper::levelRequiresApplicationFeePayment($level, $user)) {
                return to_route('portal.application.create');
            }

            if ($applicationFeeService->unpaidForCurrentIntake($user) !== null) {
                return to_route('portal.application.fee-payment');
            }

            return to_route('portal.application.track');
        }

        return redirect()->intended(route(DefaultHome::routeName($user)));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();
        $request->user()?->tokens()?->delete();

        return redirect()->intended(route('home'));
    }
}
