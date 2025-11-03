<?php

namespace App\Http\Controllers\Enrolments;

use App\DTO\Enrolments\ClassListDto;
use App\Helpers\Helper;
use App\Helpers\WorkflowHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\ClassListRequest;
use App\Http\Resources\Enrolments\ClassListNextTopResource;
use App\Http\Resources\Enrolments\EnrolmentGroupResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Enrolments\OtherApplicationResource;
use App\Http\Resources\Institution\DepartmentApplicationStepResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Students\StudentProgram;
use App\Repositories\Institution\interface\IClassListRepository;
use App\Services\DepartmentEnrolmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Log;
use Throwable;

class ClassListController extends Controller
{
    public function __construct(protected IClassListRepository $repository, protected DepartmentEnrolmentService $departmentEnrolmentService)
    {
    }

    public function store(ClassListRequest $request)
    {
        try {
            $classLists = array_merge(
                $this->buildClassListDto($request->input('class_list', []), 'provisional'),
                $this->buildClassListDto($request->input('waiting_list', []), 'waiting')
            );

            $this->createClassLists($classLists);

            return back()->with('success', 'Class lists created successfully.');
        } catch (Throwable $e) {
            Log::error('Failed to create class lists', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'An error occurred while creating class lists. All changes have been rolled back.');
        }
    }


    /**
     * Build an array of ClassListDto objects.
     */
    private function buildClassListDto(array $ids, string $type): array
    {
        $defaultAttributes = [
            'identity_confirmed' => false,
            'disability_confirmed' => false,
            'names_confirmed' => false,
            'o_level_confirmed' => false,
            'previous_level_confirmed' => false,
            'read_write_confirmed' => false,
            'application_fee_confirmed' => false,
            'tuition_fee_confirmed' => false,
        ];

        return array_map(
            fn($id) => new ClassListDto(
                student_program_id: $id,
                type: $type,
                attributes: $defaultAttributes
            ),
            $ids
        );
    }

    /**
     * @throws Throwable
     */
    protected function createClassLists(array $classLists): void
    {
        DB::transaction(function () use ($classLists) {
            collect($classLists)->each(function ($dto) {
                $classEntry = $this->repository->create($dto);
                SendEnrolmentProgressJob::dispatch($classEntry->id, $dto->type)->withoutDelay();
            });
        });
    }


    public function update(Request $request, ClassList $classList)
    {

    }

    public function verify(StudentProgram $studentProgram)
    {
        $studentProgram->load([
            'departmentWorkflowStep',
            'institutionDepartment',
            'departmentLevel.level',
            'departmentLevel.requirement',
            'departmentCourse.course',
            'classList',
            'intakePeriod',
            'modeOfStudy',
            'student.user',
            'student.contacts',
            'student.oLevelResults.subject',
            'student.oLevelResults.grade',
            'student.oLevelResults.academicLevel',
            'student.user.ledgers.feeType',
        ]);

        $nextTop = DB::table('student_programs as sp')
            ->join('class_lists as cl', 'cl.student_program_id', '=', 'sp.id')
            ->join('students as st', 'st.id', '=', 'sp.student_id')
            ->join('users as us', 'us.id', '=', 'st.user_id')
            ->select('sp.id as application_id', 'us.first_name', 'us.middle_name', 'us.last_name')
            ->whereNotIn('sp.id', [$studentProgram->id])
            ->where('sp.institution_department_id', $studentProgram->institution_department_id)
            ->where('sp.department_level_id', $studentProgram->department_level_id)
            ->where('sp.department_course_id', $studentProgram->department_course_id)
            ->where('sp.intake_period_id', $studentProgram->intake_period_id)
            ->where('cl.type', $studentProgram->classList->type)
            ->take(10)
            ->get();
        $student = $studentProgram->student;
        $otherApplications = $student->programs()
            ->where('id', '!=', $studentProgram->id)
            ->with(['institutionDepartment', 'departmentLevel.level', 'departmentCourse.course', 'intakePeriod', 'modeOfStudy', 'classList'])
            ->get();

        return Inertia::render('enrolments/ApplicationVerification', [
            'application' => EnrolmentResource::make($studentProgram),
            'nextTop' => ClassListNextTopResource::collection($nextTop),
            'otherApplications' => OtherApplicationResource::collection($otherApplications),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function classLists(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorize('viewDepartmentMetaData');

        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->departmentEnrolmentService->extractFilters();

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
        $results = $this->departmentEnrolmentService->queryClassLists($institutionDepartment->id, $departmentLevel->id, $intakePeriod->id, $modeOfStudy->id, $courseId);

        // ------------------------------------------------------------
        // 3. Prepare data for Inertia
        // ------------------------------------------------------------
        return Inertia::render('enrolments/ClassList', [
            'department' => InstitutionDepartmentResource::make($institutionDepartment),
            'level' => DepartmentLevelResource::make($departmentLevel),
            'intakePeriod' => IntakePeriodResource::make($intakePeriod),
            'modeOfStudy' => ModeOfStudyResource::make($modeOfStudy),
            'classSize' => $courseId
                ? $this->departmentEnrolmentService->getClassSize($institutionDepartment, $departmentLevel->id, $courseId, $intakePeriod->id, $modeOfStudy->id)
                : 0,
            'enrolments' => EnrolmentGroupResource::make($results),
            'modesOfStudy' => ModeOfStudyResource::collection($modesOfStudy),
            'intakePeriods' => IntakePeriodResource::collection($intakePeriods),
            'course' => $departmentCourse ? ['name' => $departmentCourse?->course?->name, 'department_course_id' => $courseId] : null,
        ]);
    }
}
