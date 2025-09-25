<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class DashboardController extends Controller
{

    public function __invoke()
    {
        $this->authorize('viewDashboard');
        return Inertia::render('dashboard/Index');
    }

}
