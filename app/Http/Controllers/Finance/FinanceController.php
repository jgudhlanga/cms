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
        $user = $request->user();

        abort_unless(
            $user !== null && ($user->can('viewFinances') || $user->can('exportToPastel')),
            403,
        );

        return Inertia::render('finance/Index', []);
    }

    public function reconciliation(Request $request): Response
    {
        $this->authorize('viewFinances');

        return Inertia::render('finance/Reconciliation', []);
    }
}
