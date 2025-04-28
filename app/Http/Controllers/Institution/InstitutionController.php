<?php

namespace App\Http\Controllers\Institution;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InstitutionController extends Controller
{

    public function __invoke(Request $request)
    {
        return Inertia::render('institution/SetupIndex', []);
    }
}
