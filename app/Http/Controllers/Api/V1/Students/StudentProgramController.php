<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Students;

use App\Services\Students\StudentPortalDashboardService;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Responses\MetaResponse;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

class StudentProgramController extends JsonApiController
{
    public function __construct(
        protected StudentPortalDashboardService $dashboardService,
    ) {}

    public function dashboardStats(Request $request): MetaResponse
    {
        $user = $request->user();

        abort_unless($user !== null && $user->can('viewStudentDashboard'), 403);
        abort_unless($user->studentProfile !== null, 403);

        return MetaResponse::make($this->dashboardService->build($user));
    }
}
