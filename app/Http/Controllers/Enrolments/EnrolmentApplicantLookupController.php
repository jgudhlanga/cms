<?php

declare(strict_types=1);

namespace App\Http\Controllers\Enrolments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\LookupEnrolmentApplicantsRequest;
use App\Http\Resources\Enrolments\EnrolmentApplicantLookupResource;
use App\Services\Enrolments\EnrolmentApplicantLookupService;

class EnrolmentApplicantLookupController extends Controller
{
    public function __construct(
        private readonly EnrolmentApplicantLookupService $lookupService,
    ) {}

    public function __invoke(LookupEnrolmentApplicantsRequest $request)
    {
        $validated = $request->validated();

        $results = $this->lookupService->search([
            'type' => (string) $validated['type'],
            'intake_period_id' => (int) $validated['intake_period_id'],
            'institution_department_id' => isset($validated['institution_department_id'])
                ? (int) $validated['institution_department_id']
                : null,
            'department_level_id' => isset($validated['department_level_id'])
                ? (int) $validated['department_level_id']
                : null,
            'department_course_id' => isset($validated['department_course_id'])
                ? (int) $validated['department_course_id']
                : null,
            'q' => $validated['q'] ?? null,
        ]);

        return EnrolmentApplicantLookupResource::collection($results);
    }
}
