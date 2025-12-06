<?php

namespace App\Http\Controllers\Accommodations;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AccommodationController extends Controller
{

    public function index()
    {
        return Inertia::render('accommodations/Index');
    }

}
