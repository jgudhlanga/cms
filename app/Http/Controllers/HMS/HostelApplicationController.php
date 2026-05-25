<?php

namespace App\Http\Controllers\HMS;

use App\Http\Controllers\Controller;
use App\Models\HMS\HostelApplication;
use Inertia\Inertia;

class HostelApplicationController extends Controller
{
    public function showApplication(HostelApplication $hostelApplication)
    {
        $this->authorize('view', $hostelApplication);

        return Inertia::render('hms/applications/Show', [
            'applicationId' => $hostelApplication->id,
        ]);
    }
}
