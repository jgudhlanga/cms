<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\LookupStudentsRequest;
use App\Http\Resources\Students\StudentLookupResource;
use App\Services\Students\StudentEnrolmentLookupService;

class StudentLookupController extends Controller
{
    public function __construct(
        private readonly StudentEnrolmentLookupService $lookupService,
    ) {}

    public function __invoke(LookupStudentsRequest $request)
    {
        $validated = $request->validated();

        $results = $this->lookupService->search([
            'institution_department_id' => $validated['institution_department_id'] ?? null,
            'department_level_id' => $validated['department_level_id'] ?? null,
            'department_course_id' => $validated['department_course_id'] ?? null,
            'name' => $validated['name'] ?? null,
            'search' => $validated['search'] ?? null,
        ]);

        return StudentLookupResource::collection($results);
    }
}
