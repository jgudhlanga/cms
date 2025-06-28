<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __invoke(Request $request)
    {
        $this->authorize('viewSettings');
        return Inertia::render('settings/Index', []);
    }
}
