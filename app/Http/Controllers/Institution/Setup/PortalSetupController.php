<?php

namespace App\Http\Controllers\Institution\Setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PortalSetupController extends Controller
{

    public function index(Request $request)
    {
        return Inertia::render('institution/portal/Setup', []);
    }

    public function workflows(Request $request)
    {
        return Inertia::render('institution/portal/ApplicationWorkflow', []);
    }
}
