<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = request()->user();
        if ($user->hasRole(RoleEnum::STUDENT)) {
            return to_route('portal.index', compact('user'));
        }
        $this->authorize('viewDashboard');
        return Inertia::render('dashboard/Index', []);
    }
}
