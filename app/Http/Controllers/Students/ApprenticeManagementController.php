<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Students\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprenticeManagementController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Student::class);
        return Inertia::render('students/apprentices/Index');
    }

    public function showImport()
    {
        $this->authorize('create', Student::class);
        return Inertia::render('students/apprentices/Import');
    }

    public function processImport(Request $request)
    {

    }
}
