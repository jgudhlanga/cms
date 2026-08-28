<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Maintenance\FaultyApplicationResource;
use App\Services\Maintenance\Students\FaultyApplicationsService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Inertia\Inertia;
use Inertia\Response;

class FaultyApplicationsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('maintenance/FaultyApplications');
    }

    public function data(Request $request, FaultyApplicationsService $service): AnonymousResourceCollection
    {
        $search = $request->string('search')->toString();

        return FaultyApplicationResource::collection(
            $service->paginate($search !== '' ? $search : null),
        );
    }
}
