<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Traits\HttpUtil;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use HttpUtil;

    public function __invoke()
    {
        if ($redirect = $this->redirectStudents()) {
            return $redirect;
        }
        $this->authorize('viewDashboard');
        return Inertia::render('dashboard/Index', []);
    }
}
