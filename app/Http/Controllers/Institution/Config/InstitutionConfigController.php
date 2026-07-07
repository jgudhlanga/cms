<?php

namespace App\Http\Controllers\Institution\Config;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionConfigController extends Controller
{

    public function index(Request $request): Response
    {
        $this->authorize('viewInstitutionSettings');

        return Inertia::render('institution/config/Setup', []);
    }
}
