<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Acl\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Institution\Level;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\RegistrationAvailabilityService;
use App\Services\Students\ReturningStudentContextService;
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
    public function store(LoginRequest $request, ApplicationFeeService $applicationFeeService, RegistrationAvailabilityService $registrationAvailability, ReturningStudentContextService $returningStudentContext): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $user = request()->user();
        if ($user->hasRole(RoleEnum::STUDENT->name())) {
            if ($user->has_student_profile) {
                $student = $user->studentProfile;
                if ($student !== null && $returningStudentContext->needsContinueInClassPage($student)) {
                    return to_route('portal.returning-student.continue.show');
                }
                if ($student !== null && $returningStudentContext->canStartApplication($student)) {
                    return to_route('portal.profile.applications');
                }

                return to_route('portal.dashboard');
            }

            if (! $registrationAvailability->isRegistrationOpen()) {
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

            return to_route('portal.application.level-options');
        }

        return redirect()->intended();
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
