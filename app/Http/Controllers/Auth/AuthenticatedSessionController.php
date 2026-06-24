<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Acl\RoleEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Institution\Level;
use App\Services\Students\ApplicationFeeService;
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
    public function store(LoginRequest $request, ApplicationFeeService $applicationFeeService): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $user = request()->user();
        if ($user->hasRole(RoleEnum::STUDENT->name())) {
            if ($user->has_student_profile) {
                return to_route('portal.dashboard');
            }

            $applicationFee = $applicationFeeService->activeApplicationFee($user);

            if ($applicationFee !== null) {
                if ($applicationFee->status === ApplicationFeeStatusEnum::SUBMITTED) {
                    return to_route('portal.applications');
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

            if ($level !== null && ! $level->has_application_fee_payment) {
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
