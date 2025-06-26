<?php

namespace App\Http\Controllers\Institution\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalSetupController extends Controller
{

    public function __invoke(Request $request)
    {
        return Inertia::render('institution/portal/Setup', []);
    }
}
