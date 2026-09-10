<?php

declare(strict_types=1);

namespace App\Services\Institution\Reconciliation;

use App\Actions\Students\ContinueStudentEnrolmentAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Importers\Institution\EnrolmentVsClassListImporter;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Services\Enrollment\EnrollmentLookupService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class EnrolmentVsClassListImportService
{
    public function __construct(
        private readonly EnrolmentVsClassListImporter $importer,
        private readonly EnrollmentLookupService $lookupService,
        private readonly DepartmentReconciliationOfferingMatcher $offeringMatcher,
        private readonly ContinueStudentEnrolmentAction $continueStudentEnrolmentAction,
    ) {}

    /**
     * @return array{
     *     summary: array<string, int>,
     *     rows: list<array<string, mixed>>,
     *     extras: list<array<string, mixed>>,
     * }
     */
    public function preview(
        InstitutionDepartment $department,
        UploadedFile $file,
        int $calendarYear,
        ?int $modeOfStudyId = null,
        ?int $departmentLevelId = null,
        ?int $departmentCourseId = null,
    ): array {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().($extension !== '' ? '.'.$extension : '.xlsx');
        $storedPath = $file->storeAs('department-reconciliation/enrolment-vs-class-list', $filename, 'ingest');
        $absolutePath = Storage::disk('ingest')->path($storedPath);

        try {
            $parsed = $this->importer->parse($absolutePath);
        } finally {
            Storage::disk('ingest')->delete($storedPath);
        }

        $rows = [];
        $summary = [
            'total' => 0,
            'matched' => 0,
            'elevate' => 0,
            'wrongPlace' => 0,
            'inAdmissions' => 0,
            'notFound' => 0,
            'invalid' => 0,
            'selectable' => 0,
            'extras' => 0,
        ];

        $fileStudentNumbers = [];

        foreach ($parsed['rows'] as $parsedRow) {
            $previewRow = $this->buildPreviewRow(
                $department,
                $parsedRow,
                $calendarYear,
                $modeOfStudyId,
                $departmentLevelId,
                $departmentCourseId,
            );

            $rows[] = $previewRow;
            $summary['total']++;
            $summary[$this->summaryKeyForStatus($previewRow['status'])]++;

            if ($previewRow['isSelectable']) {
                $summary['selectable']++;
            }

            if (is_string($previewRow['studentNumber']) && $previewRow['studentNumber'] !== '') {
                $fileStudentNumbers[] = EnrollmentLookupService::normalizeStudentNumber($previewRow['studentNumber']);
            }
        }

        $extras = $this->buildExtras(
            $department,
            $calendarYear,
            array_values(array_unique($fileStudentNumbers)),
            $modeOfStudyId,
            $departmentLevelId,
            $departmentCourseId,
        );

        $summary['extras'] = count($extras);

        return [
            'summary' => $summary,
            'rows' => $rows,
            'extras' => $extras,
        ];
    }

    /**
     * @param  list<array{rowNumber: int, studentApplicationId: int}>  $rows
     * @return array{
     *     summary: array{requested: int, moved: int, skipped: int},
     *     rows: list<array{rowNumber: int, status: string, reason?: string}>,
     * }
     */
    public function process(InstitutionDepartment $department, array $rows, int $calendarYear): array
    {
        $results = [];
        $moved = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $outcome = $this->processRow(
                $department,
                (int) $row['rowNumber'],
                (int) $row['studentApplicationId'],
                $calendarYear,
            );

            $results[] = $outcome;

            if ($outcome['status'] === 'moved') {
                $moved++;
            } else {
                $skipped++;
            }
        }

        return [
            'summary' => [
                'requested' => count($rows),
                'moved' => $moved,
                'skipped' => $skipped,
            ],
            'rows' => $results,
        ];
    }

    /**
     * @param  array{
     *     rowNumber: int,
     *     studentNumber: string|null,
     *     level: string|null,
     *     course: string|null,
     * }  $parsedRow
     * @return array<string, mixed>
     */
    private function buildPreviewRow(
        InstitutionDepartment $department,
        array $parsedRow,
        int $calendarYear,
        ?int $modeOfStudyId,
        ?int $filterLevelId,
        ?int $filterCourseId,
    ): array {
        $base = [
            'rowNumber' => $parsedRow['rowNumber'],
            'studentNumber' => $parsedRow['studentNumber'],
            'fileLevel' => $parsedRow['level'],
            'fileCourse' => $parsedRow['course'],
            'studentId' => null,
            'studentName' => null,
            'studentApplicationId' => null,
            'workflowStep' => null,
            'classListType' => null,
            'systemDepartment' => null,
            'systemLevel' => null,
            'systemCourse' => null,
            'highlight' => null,
            'errors' => [],
            'skipReasons' => [],
            'isSelectable' => false,
            'status' => 'invalid',
        ];

        if ($parsedRow['studentNumber'] === null) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_missing_student_number')],
                'skipReasons' => [__('trans.department_reconciliation_missing_student_number')],
            ]);
        }

        $departmentLevel = $this->offeringMatcher->findDepartmentLevel($department, $parsedRow['level']);
        $departmentCourse = $this->offeringMatcher->findDepartmentCourse($department, $parsedRow['course']);

        if ($parsedRow['level'] === null || ! $departmentLevel instanceof DepartmentLevel) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_unknown_level')],
                'skipReasons' => [__('trans.department_reconciliation_unknown_level')],
            ]);
        }

        if ($parsedRow['course'] === null || ! $departmentCourse instanceof DepartmentCourse) {
            return array_merge($base, [
                'errors' => [__('trans.department_reconciliation_unknown_course')],
                'skipReasons' => [__('trans.department_reconciliation_unknown_course')],
            ]);
        }

        if ($filterLevelId !== null && (int) $departmentLevel->id !== $filterLevelId) {
            return array_merge($base, [
                'status' => 'invalid',
                'errors' => [__('trans.department_reconciliation_filtered_out')],
                'skipReasons' => [__('trans.department_reconciliation_filtered_out')],
            ]);
        }

        if ($filterCourseId !== null && (int) $departmentCourse->id !== $filterCourseId) {
            return array_merge($base, [
                'status' => 'invalid',
                'errors' => [__('trans.department_reconciliation_filtered_out')],
                'skipReasons' => [__('trans.department_reconciliation_filtered_out')],
            ]);
        }

        $student = $this->lookupService->findStudentByStudentNumber($parsedRow['studentNumber']);

        if (! $student instanceof Student) {
            return array_merge($base, [
                'status' => 'not_found',
                'errors' => [__('trans.department_reconciliation_student_not_found')],
                'skipReasons' => [__('trans.department_reconciliation_student_not_found')],
            ]);
        }

        $base['studentId'] = $student->id;
        $base['studentName'] = $student->user?->full_name;

        $yearEnrolments = $this->yearEnrolmentsForStudent($student->id, $calendarYear, $modeOfStudyId);
        $deptEnrolment = $yearEnrolments->first(
            fn (StudentEnrolment $enrolment): bool => (int) $enrolment->institution_department_id === (int) $department->id,
        );

        if ($deptEnrolment instanceof StudentEnrolment) {
            $deptEnrolment->loadMissing([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'studentApplication.workflowStep',
                'studentApplication.classList',
            ]);

            $base['systemDepartment'] = $deptEnrolment->institutionDepartment?->department?->name
                ?? $deptEnrolment->institutionDepartment?->department_code;
            $base['systemLevel'] = $deptEnrolment->departmentLevel?->level?->name;
            $base['systemCourse'] = $deptEnrolment->departmentCourse?->course?->name;
            $base['studentApplicationId'] = $deptEnrolment->student_application_id;
            $base['workflowStep'] = $deptEnrolment->studentApplication?->workflowStep?->name;
            $base['classListType'] = $deptEnrolment->studentApplication?->classList?->type?->value
                ?? $deptEnrolment->studentApplication?->classList?->type;

            $levelMatches = (int) $deptEnrolment->department_level_id === (int) $departmentLevel->id;
            $courseMatches = (int) $deptEnrolment->department_course_id === (int) $departmentCourse->id;

            if ($levelMatches && $courseMatches) {
                return array_merge($base, [
                    'status' => 'matched',
                    'skipReasons' => [__('trans.department_enrolment_vs_class_list_already_enrolled')],
                ]);
            }

            return array_merge($base, [
                'status' => 'wrong_place',
                'highlight' => __('trans.department_enrolment_vs_class_list_wrong_place_here'),
                'errors' => [__('trans.department_enrolment_vs_class_list_wrong_place_here')],
                'skipReasons' => [__('trans.department_enrolment_vs_class_list_wrong_place_here')],
            ]);
        }

        $otherEnrolment = $yearEnrolments->first();

        if ($otherEnrolment instanceof StudentEnrolment) {
            $otherEnrolment->loadMissing([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
            ]);

            $base['systemDepartment'] = $otherEnrolment->institutionDepartment?->department?->name
                ?? $otherEnrolment->institutionDepartment?->department_code;
            $base['systemLevel'] = $otherEnrolment->departmentLevel?->level?->name;
            $base['systemCourse'] = $otherEnrolment->departmentCourse?->course?->name;

            return array_merge($base, [
                'status' => 'wrong_place',
                'highlight' => __('trans.department_enrolment_vs_class_list_enrolled_elsewhere'),
                'errors' => [__('trans.department_enrolment_vs_class_list_enrolled_elsewhere')],
                'skipReasons' => [__('trans.department_enrolment_vs_class_list_enrolled_elsewhere')],
            ]);
        }

        $application = $this->findEligibleApplication(
            $student->id,
            (int) $department->id,
            (int) $departmentLevel->id,
            (int) $departmentCourse->id,
            $modeOfStudyId,
        );

        if ($application instanceof StudentApplication) {
            $application->loadMissing(['workflowStep', 'classList', 'departmentLevel.level', 'departmentCourse.course']);

            $base['studentApplicationId'] = $application->id;
            $base['workflowStep'] = $application->workflowStep?->name;
            $base['classListType'] = $application->classList?->type?->value ?? $application->classList?->type;
            $base['systemLevel'] = $application->departmentLevel?->level?->name;
            $base['systemCourse'] = $application->departmentCourse?->course?->name;

            $blockReason = $this->elevationBlockReason($application);

            if ($blockReason !== null) {
                return array_merge($base, [
                    'status' => 'in_admissions',
                    'errors' => [$blockReason],
                    'skipReasons' => [$blockReason],
                ]);
            }

            // findEligibleApplication falls back to any application in the department when none
            // matches the class list's level and course. The enrolment is then built from the
            // application's offering, not the file's, so say so rather than electing silently.
            $offeringMismatch = (int) $application->department_level_id !== (int) $departmentLevel->id
                || (int) $application->department_course_id !== (int) $departmentCourse->id;

            return array_merge($base, [
                'status' => 'elevate',
                'isSelectable' => true,
                'highlight' => $offeringMismatch
                    ? __('trans.department_enrolment_vs_class_list_offering_mismatch')
                    : null,
                'errors' => $offeringMismatch
                    ? [__('trans.department_enrolment_vs_class_list_offering_mismatch')]
                    : [],
            ]);
        }

        $anyApplication = $this->findAnyApplication($student->id, (int) $department->id);

        if ($anyApplication instanceof StudentApplication) {
            $anyApplication->loadMissing(['workflowStep', 'classList', 'departmentLevel.level', 'departmentCourse.course']);

            $base['studentApplicationId'] = $anyApplication->id;
            $base['workflowStep'] = $anyApplication->workflowStep?->name;
            $base['classListType'] = $anyApplication->classList?->type?->value ?? $anyApplication->classList?->type;
            $base['systemLevel'] = $anyApplication->departmentLevel?->level?->name;
            $base['systemCourse'] = $anyApplication->departmentCourse?->course?->name;

            return array_merge($base, [
                'status' => 'in_admissions',
                'errors' => [__('trans.department_enrolment_vs_class_list_not_eligible')],
                'skipReasons' => [__('trans.department_enrolment_vs_class_list_not_eligible')],
            ]);
        }

        return array_merge($base, [
            'status' => 'not_found',
            'errors' => [__('trans.department_enrolment_vs_class_list_no_application')],
            'skipReasons' => [__('trans.department_enrolment_vs_class_list_no_application')],
        ]);
    }

    /**
     * @return Collection<int, StudentEnrolment>
     */
    private function yearEnrolmentsForStudent(int $studentId, int $calendarYear, ?int $modeOfStudyId): Collection
    {
        return StudentEnrolment::query()
            ->where('student_id', $studentId)
            ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                $query->where('calendar_year', (string) $calendarYear);
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $query) => $query->where('mode_of_study_id', $modeOfStudyId),
            )
            ->with([
                'institutionDepartment.department',
                'departmentLevel.level',
                'departmentCourse.course',
                'studentApplication.workflowStep',
                'studentApplication.classList',
            ])
            ->get();
    }

    private function findEligibleApplication(
        int $studentId,
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $departmentCourseId,
        ?int $modeOfStudyId,
    ): ?StudentApplication {
        $query = StudentApplication::query()
            ->where('student_id', $studentId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->where(function (Builder $builder): void {
                $builder->whereDoesntHave('classList')
                    ->orWhereHas('classList', function (Builder $classListQuery): void {
                        $classListQuery->where('type', '!=', ClassListTypeEnum::FAILED->value);
                    });
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $builder) => $builder->where('mode_of_study_id', $modeOfStudyId),
            )
            ->with(['workflowStep', 'classList', 'departmentLevel.level', 'departmentCourse.course'])
            ->orderByDesc('id');

        $preferred = (clone $query)
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->first();

        if ($preferred instanceof StudentApplication) {
            return $preferred;
        }

        return $query->first();
    }

    private function findAnyApplication(int $studentId, int $institutionDepartmentId): ?StudentApplication
    {
        return StudentApplication::query()
            ->where('student_id', $studentId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->with(['workflowStep', 'classList', 'departmentLevel.level', 'departmentCourse.course'])
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  list<string>  $fileStudentNumbers
     * @return list<array<string, mixed>>
     */
    private function buildExtras(
        InstitutionDepartment $department,
        int $calendarYear,
        array $fileStudentNumbers,
        ?int $modeOfStudyId,
        ?int $departmentLevelId,
        ?int $departmentCourseId,
    ): array {
        $fileLookup = array_fill_keys($fileStudentNumbers, true);

        $enrolments = StudentEnrolment::query()
            ->where('institution_department_id', $department->id)
            ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                $query->where('calendar_year', (string) $calendarYear);
            })
            ->when(
                $modeOfStudyId !== null,
                fn (Builder $query) => $query->where('mode_of_study_id', $modeOfStudyId),
            )
            ->when(
                $departmentLevelId !== null,
                fn (Builder $query) => $query->where('department_level_id', $departmentLevelId),
            )
            ->when(
                $departmentCourseId !== null,
                fn (Builder $query) => $query->where('department_course_id', $departmentCourseId),
            )
            ->with([
                'student.user',
                'departmentLevel.level',
                'departmentCourse.course',
            ])
            ->get();

        $extras = [];

        foreach ($enrolments as $enrolment) {
            $studentNumber = $enrolment->student?->student_number;

            if (! is_string($studentNumber) || $studentNumber === '') {
                continue;
            }

            $normalized = EnrollmentLookupService::normalizeStudentNumber($studentNumber);

            if (isset($fileLookup[$normalized])) {
                continue;
            }

            $extras[] = [
                'studentEnrolmentId' => $enrolment->id,
                'studentId' => $enrolment->student_id,
                'studentNumber' => $studentNumber,
                'studentName' => $enrolment->student?->user?->full_name,
                'level' => $enrolment->departmentLevel?->level?->name,
                'course' => $enrolment->departmentCourse?->course?->name,
                'highlight' => __('trans.department_enrolment_vs_class_list_extra_on_system'),
            ];
        }

        return $extras;
    }

    /**
     * @return array{rowNumber: int, status: string, reason?: string}
     */
    private function processRow(
        InstitutionDepartment $department,
        int $rowNumber,
        int $studentApplicationId,
        int $calendarYear,
    ): array {
        try {
            return DB::transaction(function () use (
                $department,
                $rowNumber,
                $studentApplicationId,
                $calendarYear,
            ): array {
                $application = StudentApplication::query()
                    ->with(['classList', 'workflowStep', 'student'])
                    ->find($studentApplicationId);

                if (! $application instanceof StudentApplication) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_enrolment_vs_class_list_no_application'),
                    ];
                }

                if ((int) $application->institution_department_id !== (int) $department->id) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_enrolment_vs_class_list_enrolled_elsewhere'),
                    ];
                }

                // Re-run the same eligibility gate the preview applies. The uploaded file is gone
                // by this point and the row ids arrive from the client, so this is the only thing
                // standing between a hand-crafted request and a force-enrolled applicant.
                $blockReason = $this->elevationBlockReason($application);

                if ($blockReason !== null) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => $blockReason,
                    ];
                }

                $existing = StudentEnrolment::query()
                    ->where('student_application_id', $application->id)
                    ->whereHas('academicCalendar', function (Builder $query) use ($calendarYear): void {
                        $query->where('calendar_year', (string) $calendarYear);
                    })
                    ->exists();

                if ($existing) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_enrolment_vs_class_list_already_enrolled'),
                    ];
                }

                // The requested year guards the duplicate check above, so it must also drive the
                // write. Without an anchor the enrolment is created against whatever calendar
                // covers today, silently filing a 2025 reconciliation under 2026.
                $calendar = $this->targetAcademicCalendar($application, $calendarYear);

                if (! $calendar instanceof AcademicCalendar) {
                    return [
                        'rowNumber' => $rowNumber,
                        'status' => 'skipped',
                        'reason' => __('trans.department_enrolment_vs_class_list_no_calendar_for_year', [
                            'year' => (string) $calendarYear,
                        ]),
                    ];
                }

                $this->continueStudentEnrolmentAction->execute(
                    $application,
                    $this->enrolmentAnchorFor($calendar),
                );

                return [
                    'rowNumber' => $rowNumber,
                    'status' => 'moved',
                ];
            });
        } catch (StudentEnrolmentResolutionException $exception) {
            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => $exception->getMessage(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'rowNumber' => $rowNumber,
                'status' => 'skipped',
                'reason' => __('trans.department_enrolment_vs_class_list_process_row_failed'),
            ];
        }
    }

    /**
     * The academic calendar the reconciled year refers to, for this application's calendar type.
     */
    private function targetAcademicCalendar(StudentApplication $application, int $calendarYear): ?AcademicCalendar
    {
        $application->loadMissing('departmentLevel.level');

        $calendarType = $application->departmentLevel?->level?->calendar_type;
        $typeValue = $calendarType instanceof AcademicCalendarTypeEnum
            ? $calendarType->value
            : (is_string($calendarType) ? $calendarType : null);

        if ($typeValue === null) {
            return null;
        }

        return AcademicCalendar::query()
            ->where('type', $typeValue)
            ->where('calendar_year', (string) $calendarYear)
            ->orderBy('opening_date')
            ->first();
    }

    /**
     * The point in time to enrol as of.
     *
     * Null (meaning "now") whenever today already falls inside the target calendar, so the ordinary
     * current-year path keeps its existing behaviour — including how many phases the semester sync
     * materialises. Only an out-of-range year gets an explicit anchor: the close of a past calendar,
     * or the opening of a future one.
     */
    private function enrolmentAnchorFor(AcademicCalendar $calendar): ?CarbonInterface
    {
        $timezone = (string) config('app.timezone');
        $today = Carbon::now($timezone)->startOfDay();

        $opening = $calendar->opening_date !== null
            ? Carbon::parse((string) $calendar->opening_date, $timezone)->startOfDay()
            : null;
        $closing = $calendar->closing_date !== null
            ? Carbon::parse((string) $calendar->closing_date, $timezone)->startOfDay()
            : null;

        if ($opening !== null && $today->lt($opening)) {
            return $opening;
        }

        if ($closing !== null && $today->gt($closing)) {
            return $closing;
        }

        return null;
    }

    /**
     * Workflow steps that mean the applicant is still moving through admissions and must not be
     * elevated straight to enrolled. A null step is allowed through: legacy records predate the
     * workflow and reconciling them is the point of this tool.
     *
     * @var list<WorkflowStepEnum>
     */
    private const BLOCKED_WORKFLOW_STEPS = [
        WorkflowStepEnum::REGISTRATION_FEE,
        WorkflowStepEnum::REVIEW,
        WorkflowStepEnum::REQUIREMENTS,
        WorkflowStepEnum::WAITLISTED,
        WorkflowStepEnum::REJECTED,
    ];

    /**
     * Shared by preview and process so the two can never drift apart.
     *
     * @return string|null Null when the application may be elevated, otherwise the reason it may not.
     */
    private function elevationBlockReason(StudentApplication $application): ?string
    {
        $classListType = $application->classList?->type;

        if ($classListType === ClassListTypeEnum::FAILED || $classListType === ClassListTypeEnum::FAILED->value) {
            return __('trans.department_enrolment_vs_class_list_not_eligible');
        }

        $stepSlug = $application->workflowStep?->slug;

        if ($stepSlug === null) {
            return null;
        }

        foreach (self::BLOCKED_WORKFLOW_STEPS as $blocked) {
            if ($stepSlug === $blocked->slug()) {
                return __('trans.department_enrolment_vs_class_list_not_eligible');
            }
        }

        return null;
    }

    private function summaryKeyForStatus(string $status): string
    {
        return match ($status) {
            'matched' => 'matched',
            'elevate' => 'elevate',
            'wrong_place' => 'wrongPlace',
            'in_admissions' => 'inAdmissions',
            'not_found' => 'notFound',
            default => 'invalid',
        };
    }
}
