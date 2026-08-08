<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Students;

use App\Enums\Students\StudentClearanceSection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\UpdateStudentClearanceRequest;
use App\Http\Resources\Students\StudentClearanceResource;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\Student;
use App\Models\Students\StudentClearance;
use App\Services\Institution\InstitutionFeatureService;
use App\Services\Students\StudentClearanceService;
use App\Services\Students\StudentExamResultAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StudentClearanceController extends Controller
{
    public function __construct(
        private readonly StudentClearanceService $clearanceService,
        private readonly StudentExamResultAccessService $accessService,
        private readonly InstitutionFeatureService $featureService,
    ) {}

    public function show(Request $request, Student $student): JsonResponse
    {
        $this->authorizeAnyClearancePermission();

        $allowOnlineClearance = $this->featureService->allowsOnlineClearance((int) $student->tenant_id);
        $enrolment = $this->accessService->resolveEnrolmentContext($student);
        $calendarType = $this->accessService->resolveCalendarType($enrolment);

        $defaultYear = $this->accessService->resolveCalendarYear($enrolment);
        $calendarYear = (int) ($request->integer('calendar_year') ?: ($defaultYear ?? 0));
        $semesterId = (int) ($request->integer('semester_id') ?: $enrolment?->semester_id);

        $clearance = null;
        if ($calendarYear >= 2000 && $semesterId > 0) {
            $clearance = $this->clearanceService
                ->findOrNew($student, $calendarYear, $semesterId);
            $clearance->loadMissing([
                'student',
                'accountsClearedBy',
                'libraryClearedBy',
                'securityClearedBy',
                'hostelClearedBy',
                'departmentClearedBy',
            ]);
            if (! $clearance->exists) {
                $clearance->calendar_year = $calendarYear;
                $clearance->semester_id = $semesterId;
                $clearance->student_id = $student->id;
                $clearance->tenant_id = $student->tenant_id;
            }
        }

        return response()->json([
            'data' => [
                'allowOnlineClearance' => $allowOnlineClearance,
                'clearance' => $clearance
                    ? $this->formatClearanceResource($clearance, $request, $allowOnlineClearance)
                    : null,
                'options' => [
                    'semesters' => Semester::query()
                        ->where('slug', 'like', $calendarType->value.'-%')
                        ->orderBy('name')
                        ->get(['id', 'name', 'slug'])
                        ->map(fn (Semester $semester): array => [
                            'id' => $semester->id,
                            'label' => $semester->name,
                            'slug' => $semester->slug,
                        ]),
                ],
                'defaults' => [
                    'calendarYear' => $calendarYear >= 2000 ? $calendarYear : $defaultYear,
                    'semesterId' => $semesterId > 0 ? $semesterId : null,
                ],
                'calendarType' => $calendarType->value,
                'identity' => [
                    'isZimbabwean' => $student->isZimbabwean(),
                    'idNumber' => $student->id_number,
                    'passportNumber' => $student->passport_number,
                    'studentNumber' => $student->student_number,
                ],
                'permissions' => collect(StudentClearanceSection::all())
                    ->mapWithKeys(fn (StudentClearanceSection $section): array => [
                        $section->value => $request->user()?->can($section->permission()) ?? false,
                    ]),
            ],
        ]);
    }

    public function update(UpdateStudentClearanceRequest $request, Student $student): JsonResponse
    {
        $allowOnlineClearance = $this->featureService->allowsOnlineClearance((int) $student->tenant_id);

        if ($request->has('sections')) {
            /** @var list<array{section: string, cleared: bool|string, notes?: string|null}> $rawSections */
            $rawSections = $request->input('sections', []);

            $sections = collect($rawSections)
                ->map(fn (array $row): array => [
                    'section' => StudentClearanceSection::from((string) $row['section']),
                    'cleared' => filter_var($row['cleared'], FILTER_VALIDATE_BOOLEAN),
                    'notes' => $row['notes'] ?? null,
                ])
                ->all();

            $this->assertAllowedSections($sections, $allowOnlineClearance);

            $clearance = $this->clearanceService->updateSections(
                $student,
                $request->integer('calendar_year'),
                $request->integer('semester_id'),
                $sections,
                $request->user(),
            );
        } else {
            $section = StudentClearanceSection::from($request->string('section')->toString());

            $this->assertAllowedSections([
                [
                    'section' => $section,
                    'cleared' => $request->boolean('cleared'),
                    'notes' => $request->input('notes'),
                ],
            ], $allowOnlineClearance);

            $clearance = $this->clearanceService->updateSection(
                $student,
                $request->integer('calendar_year'),
                $request->integer('semester_id'),
                $section,
                [
                    'cleared' => $request->boolean('cleared'),
                    'notes' => $request->input('notes'),
                ],
                $request->user(),
            );
        }

        return response()->json([
            'message' => __('trans.clearance_saved'),
            'data' => $this->formatClearanceResource($clearance, $request, $allowOnlineClearance),
        ]);
    }

    /**
     * @param  list<array{section: StudentClearanceSection, cleared: bool, notes?: string|null}>  $sections
     */
    private function assertAllowedSections(array $sections, bool $allowOnlineClearance): void
    {
        if ($allowOnlineClearance) {
            return;
        }

        foreach ($sections as $row) {
            if ($row['section'] !== StudentClearanceSection::Accounts) {
                throw ValidationException::withMessages([
                    'sections' => [__('trans.clearance_accounts_only_when_feature_off')],
                ]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatClearanceResource(
        StudentClearance $clearance,
        Request $request,
        bool $allowOnlineClearance,
    ): array {
        $data = StudentClearanceResource::make($clearance)->resolve($request);

        if (! $allowOnlineClearance) {
            $data['sections'] = array_values(array_filter(
                $data['sections'],
                fn (array $section): bool => $section['key'] === StudentClearanceSection::Accounts->value,
            ));
        }

        return $data;
    }

    private function authorizeAnyClearancePermission(): void
    {
        $user = request()->user();
        $allowed = collect(StudentClearanceSection::all())
            ->contains(fn (StudentClearanceSection $section): bool => $user?->can($section->permission()) ?? false);

        abort_unless($allowed, 403);
    }
}
