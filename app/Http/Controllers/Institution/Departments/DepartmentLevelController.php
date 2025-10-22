<?php

namespace App\Http\Controllers\Institution\Departments;

use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\Helper;
use App\Http\Resources\Enrolments\EnrolmentGroupResource;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\ModeOfStudy;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\FeeType;
use App\Models\Users\User;
use Carbon\Carbon;
use App\DTO\Institution\{DepartmentLevelDto, DepartmentLevelRequirementsDto};
use App\Http\Controllers\Controller;
use App\Http\Requests\Institution\{DepartmentLevelRequest, DepartmentLevelRequirementRequest};
use App\Http\Resources\Institution\{
    DepartmentLevelResource,
    InstitutionDepartmentResource,
    DepartmentApplicationStepResource,
    DepartmentLevelRequirementResource,
    IntakePeriodResource,
    ModeOfStudyResource
};
use App\Models\Institution\DepartmentLevel;
use App\Models\Students\StudentProgram;
use App\Models\Institution\InstitutionDepartment;
use App\Repositories\Institution\interface\IDepartmentLevelRepository;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use App\Helpers\WorkflowHelper;
use App\Models\Institution\IntakePeriod;

class DepartmentLevelController extends Controller
{
    public function __construct(protected IDepartmentLevelRepository $repository)
    {
    }

    /**
     * @throws AuthorizationException
     */
    public function departmentLevelRequirements(DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('updateDepartmentMetaData');
        $departmentLevel = DepartmentLevelResource::make($departmentLevel);
        $institutionDepartment = InstitutionDepartmentResource::make($departmentLevel->institutionDepartment);
        $levels = DepartmentLevelResource::collection($institutionDepartment->departmentLevels);
        $requirements = $departmentLevel->requirement ? DepartmentLevelRequirementResource::make($departmentLevel->requirement) : null;
        return Inertia::render('institution/departments/DepartmentLevelRequirements',
            compact('departmentLevel', 'institutionDepartment', 'levels', 'requirements'));
    }

    /**
     * @throws AuthorizationException
     */
    public function updateDepartmentLevelRequirements(DepartmentLevel $departmentLevel, DepartmentLevelRequirementRequest $request): void
    {
        $this->authorize('updateDepartmentMetaData');
        $this->repository->updateDepartmentLevelRequirements($departmentLevel, DepartmentLevelRequirementsDto::fromDepartmentLevelRequirementRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function syncDepartmentLevels(InstitutionDepartment $institutionDepartment, DepartmentLevelRequest $request): void
    {
        $this->authorize('createDepartmentMetaData');
        $this->repository->syncDepartmentLevels($institutionDepartment, DepartmentLevelDto::fromDepartmentLevelRequest($request));
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('deleteDepartmentMetaData');
        $this->repository->delete($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function restore(string $id): void
    {
        $departmentLevel = $this->repository->findTrashed($id);
        $this->authorize('restoreDepartmentMetaData');
        $this->repository->restore($departmentLevel);
    }

    /**
     * @throws AuthorizationException
     */
    public function forceDelete(DepartmentLevel $departmentLevel): void
    {
        $this->authorize('forceDeleteDepartmentMetaData');
        $this->repository->delete($departmentLevel, true);
    }

    /**
     * @throws AuthorizationException
     */
    public function enrolments(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewDepartmentMetaData');

        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->extractFilters();

        // ------------------------------------------------------------
        // 1. Resolve static/cached data
        // ------------------------------------------------------------
        $intakePeriods = cache()->rememberForever('all_intake_periods', fn() => IntakePeriod::orderByDesc('end_date')->get());
        $modesOfStudy = cache()->rememberForever('all_modes_of_study', fn() => ModeOfStudy::all());

        $intakePeriod = $intakePeriodId
            ? $intakePeriods->firstWhere('id', $intakePeriodId)
            : Helper::resolveIntakePeriod();

        $modeOfStudy = $modeOfStudyId
            ? $modesOfStudy->firstWhere('id', $modeOfStudyId)
            : Helper::resolveModeOfStudy();

        $departmentCourse = $courseId
            ? DepartmentCourse::with(['course'])->find($courseId)
            : null;

        // ------------------------------------------------------------
        // 2. Query enrolments efficiently
        // ------------------------------------------------------------
        $results = $this->queryEnrolments(
            $institutionDepartment->id,
            $departmentLevel->id,
            $intakePeriod->id,
            $modeOfStudy->id,
            $courseId
        );

        // ------------------------------------------------------------
        // 3. Prepare data for Inertia
        // ------------------------------------------------------------
        return Inertia::render('institution/enrolments/CourseLevelEnrolments', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'workflowSteps' => DepartmentApplicationStepResource::collection(WorkflowHelper::getAllSteps($institutionDepartment->id)),
            'classSize' => $courseId
                ? $this->getClassSize($institutionDepartment, $departmentLevel->id, $courseId, $intakePeriod->id, $modeOfStudy->id)
                : 0,
            'enrolments' => EnrolmentGroupResource::make($results),
            'modesOfStudy' => ModeOfStudyResource::collection($modesOfStudy),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
            'course' => $departmentCourse ? $departmentCourse?->course?->name : null,
        ]);
    }

    private function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;
        $courseId = request('department_course_id') > 0 ? (int)request('department_course_id') : null;

        return [$intakePeriodId, $modeOfStudyId, $courseId];
    }

    private function getClassSize(InstitutionDepartment $institutionDepartment, $departmentLevelId, $departmentCourseId, $intakePeriodId, $modeOfStudyId): int
    {
        return $institutionDepartment->intakeClassSizes()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->where('intake_period_id', $intakePeriodId)
            ->where('mode_of_study_id', $modeOfStudyId)->pluck('class_size')->first() ?? 0;
    }

    public function queryEnrolments(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $intakePeriodId,
        int $modeOfStudyId,
        int $courseId,
        int $perPage = 1000
    ): array
    {
        // ------------------------------------------------------------
        // 1. Cached IDs
        // ------------------------------------------------------------
        $oLevelId = cache()->rememberForever('o_level_id', fn() => AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->value('id'));
        $applicationFeeId = cache()->rememberForever('application_fee_id', fn() => FeeType::where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->value('id'));

        // ------------------------------------------------------------
        // 2. Subquery for latest student program per student
        // ------------------------------------------------------------
        $subQuery = StudentProgram::query()
            ->selectRaw('MAX(id) as id')
            ->where([
                'institution_department_id' => $institutionDepartmentId,
                'department_level_id' => $departmentLevelId,
                'intake_period_id' => $intakePeriodId,
                'mode_of_study_id' => $modeOfStudyId,
                'department_course_id' => $courseId,
            ])
            ->groupBy('student_id');

        // ------------------------------------------------------------
        // 3. Eager load all necessary relations
        // ------------------------------------------------------------
        $paginator = StudentProgram::query()
            ->with([
                'student.user:id,first_name,last_name,email',
                'student.gender:id,title',
                'student.contacts' => fn($q) => $q->orderBy('created_at')->limit(1),
                'departmentWorkflowStep.workflowStep:id,name',
            ])
            ->whereIn('id', $subQuery)
            ->select([
                'id as application_id',
                'student_id',
                'department_application_step_id',
                'application_tracking_number',
                'created_at as application_date',
            ])
            ->paginate($perPage);

        $studentPrograms = $paginator->getCollection();

        $studentIds = $studentPrograms->pluck('student_id')->unique();
        $userIds = $studentPrograms->pluck('student.user_id')->unique();

        // ------------------------------------------------------------
        // 4. Preload academic stats & results in bulk
        // ------------------------------------------------------------
        $academicStats = DB::table('student_academic_results')
            ->select('student_id', DB::raw('COUNT(DISTINCT exam_year) as exam_sittings_count'), DB::raw('MIN(exam_year) as first_exam_year'))
            ->whereIn('student_id', $studentIds)
            ->where('academic_level_id', $oLevelId)
            ->whereNull('deleted_at')
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $academicResults = DB::table('student_academic_results as sar')
            ->join('subjects as s', 'sar.subject_id', '=', 's.id')
            ->join('grades as g', 'sar.grade_id', '=', 'g.id')
            ->whereIn('sar.student_id', $studentIds)
            ->where('sar.academic_level_id', $oLevelId)
            ->whereNull('sar.deleted_at')
            ->select(
                'sar.id as result_id',
                'sar.student_id',
                'sar.subject_id',
                'sar.exam_year',
                'sar.exam_sitting',
                'sar.grade_id',
                's.name as subject',
                'g.name as grade'
            )
            ->orderBy('sar.exam_year')
            ->orderBy('g.name')
            ->get()
            ->groupBy('student_id');

        // ------------------------------------------------------------
        // 5. Preload receipts in bulk
        // ------------------------------------------------------------
        $receipts = Ledger::query()
            ->whereIn('ledgerable_id', $userIds)
            ->where('ledgerable_type', User::class)
            ->whereNull('deleted_at')
            ->where([
                'fee_type_id' => $applicationFeeId,
                'intake_period_id' => $intakePeriodId,
                'payment_status' => 'paid',
                'type' => 'receipt',
            ])
            ->select('ledgerable_id as user_id', 'id as receipt_id', 'amount as receipt_amount')
            ->get()
            ->keyBy('user_id');

        // ------------------------------------------------------------
        // 6. Transform students
        // ------------------------------------------------------------
        $studentPrograms->transform(function ($sp) use ($academicStats, $academicResults, $receipts) {
            $student = $sp->student;
            $user = $student->user;

            $sp->student_name = "{$user->first_name} {$user->last_name}";
            $sp->email = $user->email;
            $sp->phone_number = $student->contacts->first()?->phone_number;
            $sp->student_number = $student->student_number;
            $sp->disability_status = $student->disability_status;
            $sp->gender = $student->gender->title ?? null;
            $sp->workflow_step = $sp->departmentWorkflowStep?->workflowStep?->name;
            $sp->application_date = Carbon::parse($sp->application_date)->format('Y-m-d');

            // Attach academic stats
            $stats = $academicStats->get($student->id);
            $sp->exam_sittings_count = $stats->exam_sittings_count ?? 0;
            $sp->first_exam_year = $stats->first_exam_year ?? null;

            // Attach receipt
            $receipt = $receipts->get($student->user_id);
            $sp->receipt_id = $receipt->receipt_id ?? null;
            $sp->receipt_amount = $receipt->receipt_amount ?? null;

            // Attach full academic results
            $sp->academic_results = $academicResults->get($student->id, collect());

            return $sp;
        });

        // ------------------------------------------------------------
        // 7. Group students by priority
        // ------------------------------------------------------------
        $grouped = [
            'disabled' => $studentPrograms
                ->filter(fn($sp) => strtolower($sp->disability_status) === 'yes')
                ->sortBy('student_name')
                ->values(),

            'females' => $studentPrograms
                ->filter(fn($sp) => strtolower($sp->disability_status) !== 'yes' &&
                    strtolower($sp->gender) === 'female')
                ->sortBy('student_name')
                ->values(),

            'males' => $studentPrograms
                ->filter(fn($sp) => strtolower($sp->disability_status) !== 'yes' &&
                    strtolower($sp->gender) === 'male')
                ->sortBy('student_name')
                ->values(),

            'others' => $studentPrograms
                ->filter(fn($sp) => strtolower($sp->disability_status) !== 'yes' &&
                    !in_array(strtolower($sp->gender), ['male', 'female']))
                ->sortBy('student_name')
                ->values(),
        ];

        // ------------------------------------------------------------
        // 8. Return
        // ------------------------------------------------------------
        return [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'links' => $paginator->linkCollection(),
            ],
            'groups' => $grouped,
        ];
    }

}
