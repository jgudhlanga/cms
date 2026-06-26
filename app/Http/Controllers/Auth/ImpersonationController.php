<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Services\Auth\ImpersonationLandingResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonateManager $impersonateManager,
        private readonly ImpersonationLandingResolver $landingResolver,
    ) {}

    public function take(Request $request, int $id, ?string $guardName = null): RedirectResponse
    {
        $guardName = $guardName ?? $this->impersonateManager->getDefaultSessionGuard();

        if ($this->impersonateManager->isImpersonating()) {
            $this->impersonateManager->leave();
        }

        $impersonator = auth($guardName)->user();

        if (! $impersonator) {
            abort(403);
        }

        if ($id === $impersonator->getAuthIdentifier()) {
            abort(403);
        }

        if (! $impersonator->canImpersonate()) {
            abort(403);
        }

        $userToImpersonate = $this->impersonateManager->findUserById($id, $guardName);

        if (method_exists($userToImpersonate, 'canBeImpersonated') && $userToImpersonate->canBeImpersonated()) {
            if ($this->impersonateManager->take($impersonator, $userToImpersonate, $guardName)) {
                /** @var User $userToImpersonate */
                return redirect()->to($this->landingResolver->landingUrl($userToImpersonate));
            }
        }

        return redirect()->back();
    }
}
