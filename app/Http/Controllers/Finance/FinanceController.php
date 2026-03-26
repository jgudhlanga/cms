<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewFinances');

        return Inertia::render('finance/Index', []);
    }

    public function settings(Request $request): Response
    {
        $this->authorize('viewFinanceSettings');

        return Inertia::render('finance/Settings', []);
    }
}
