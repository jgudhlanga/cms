<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Acl\RoleEnum;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;

class ImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonateManager $impersonateManager
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
                return redirect()->to($this->resolveTakeRedirect($userToImpersonate));
            }
        }

        return redirect()->back();
    }

    private function resolveTakeRedirect(Authenticatable $impersonatedUser): string
    {
        if (method_exists($impersonatedUser, 'hasRole') && $impersonatedUser->hasRole(RoleEnum::STUDENT->name())) {
            $hasStudentProfile = method_exists($impersonatedUser, 'getHasStudentProfileAttribute')
                ? $impersonatedUser->getHasStudentProfileAttribute()
                : false;

            return $hasStudentProfile
                ? route('portal.dashboard')
                : route('portal.application.level-options');
        }

        return route('dashboard');
    }
}
