<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            logger('1. Email already marked as verified');
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        } else {
            logger('2. Email already marked as verified');
        }

        if ($request->user()->markEmailAsVerified()) {
            logger('3. Email marked as verified');
            event(new Verified($request->user()));
        } else {
            logger('4. NotEmail marked as verified');
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }
}
