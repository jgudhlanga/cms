<?php

namespace App\Http\Controllers\Enrolments;

use App\DTO\Enrolments\ClassListDto;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Exceptions\Students\StudentEnrolmentResolutionException;
use App\Helpers\DepartmentHelper;
use App\Helpers\DropdownHelper;
use App\Helpers\EnrolmentHelper;
use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\AddToClassListRequest;
use App\Http\Requests\Enrolments\ClassListRequest;
use App\Http\Requests\Enrolments\UpdateClassEntryRequest;
use App\Http\Resources\Enrolments\ClassListNextTopResource;
use App\Http\Resources\Enrolments\EnrolmentGroupResource;
use App\Http\Resources\Enrolments\EnrolmentResource;
use App\Http\Resources\Enrolments\OtherApplicationResource;
use App\Http\Resources\Institution\DepartmentLevelResource;
use App\Http\Resources\Institution\InstitutionDepartmentResource;
use App\Http\Resources\Institution\IntakePeriodResource;
use App\Http\Resources\Institution\ModeOfStudyResource;
use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Shared\FeeType;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentApplication;
use App\Repositories\Institution\interface\IClassListRepository;
use App\Services\DepartmentEnrolmentService;
use App\Services\Students\ResolveStudentEnrolmentAttributesService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ClassListController extends Controller
{
    public function __construct(
        protected IClassListRepository $repository,
        protected DepartmentEnrolmentService $departmentEnrolmentService,
        protected ResolveStudentEnrolmentAttributesService $resolveStudentEnrolmentAttributes,
    ) {}

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
            return back()->with('error', 'An error occurred while creating class lists. All changes have been rolled back.');
        }
    }

    public function addToClassList(StudentApplication $studentApplication, AddToClassListRequest $request)
    {
        try {
            $defaultAttributes = [
                'identity_confirmed' => false,
                'disability_confirmed' => false,
                'names_confirmed' => false,
                'o_level_confirmed' => false,
                'previous_level_confirmed' => false,
                'read_write_confirmed' => false,
                'application_fee_confirmed' => false,
                'proof_of_payment_confirmed' => false,
                'passport_photos_confirmed' => false,
                'original_birth_certificate_confirmed' => false,
                'original_national_identity_confirmed' => false,
                'original_education_certificates_confirmed' => false,
            ];
            $dto = new ClassListDto(
                student_application_id: $studentApplication->id,
                type: $request->input('type'),
                attributes: $defaultAttributes
            );
            $classEntry = $this->repository->create($dto);
            $details = $this->getClassEntryDetails($classEntry->id);
            SendEnrolmentProgressJob::dispatch(
                $classEntry->id,
                $dto->type,
                $details->institution_department_id,
                $details->department,
                $details->level,
                $details->course)->withoutDelay();

            return back()->with('success', 'Class lists created successfully.');
        } catch (Throwable $e) {
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
            'proof_of_payment_confirmed' => false,
            'passport_photos_confirmed' => false,
            'original_birth_certificate_confirmed' => false,
            'original_national_identity_confirmed' => false,
            'original_education_certificates_confirmed' => false,
        ];

        return array_map(
            fn ($id) => new ClassListDto(
                student_application_id: $id,
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
                $details = $this->getClassEntryDetails($classEntry->id);
                SendEnrolmentProgressJob::dispatch(
                    $classEntry->id,
                    $dto->type,
                    $details->institution_department_id,
                    $details->department,
                    $details->level,
                    $details->course)->withoutDelay();
            });
        });
    }

    protected function getClassEntryDetails(int $classListId)
    {
        return DB::table('class_lists as cl')
            ->join('student_applications as sp', 'sp.id', '=', 'cl.student_application_id')
            ->join('students as st', 'st.id', '=', 'sp.student_id')
            ->join('institution_departments as idp', 'idp.id', '=', 'sp.institution_department_id')
            ->join('departments as dp', 'dp.id', '=', 'idp.department_id')
            ->join('department_levels as dl', 'dl.id', '=', 'sp.department_level_id')
            ->join('levels as lv', 'lv.id', '=', 'dl.level_id')
            ->join('department_courses as dc', 'dc.id', '=', 'sp.department_course_id')
            ->join('courses as cs', 'cs.id', '=', 'dc.course_id')
            ->join('users as us', 'us.id', '=', 'st.user_id')
            ->where('cl.id', $classListId)
            ->select([
                'cl.id',
                'us.first_name',
                'us.last_name',
                'us.email',
                'sp.institution_department_id',
                'dp.name as department',
                'lv.name as level',
                'cs.name as course',
            ])->first();
    }

    public function update(UpdateClassEntryRequest $request, StudentApplication $studentApplication)
    {
        try {
            $type = $request->input('type', 'provisional');
            // Get class list
            $entry = ClassList::where('student_application_id', $studentApplication->id)->first();
            if (! $entry) {
                return back()->with('error', 'Class list entry not found for the specified student program.');
            }

            // Update class list entry only if identity_confirmed  disability_confirmed names_confirmed are true
            $entry->attributes = array_merge($entry->attributes ?? [], [
                'identity_confirmed' => $request->boolean('identity_confirmed'),
                'disability_confirmed' => $request->boolean('disability_confirmed'),
                'names_confirmed' => $request->boolean('names_confirmed'),
                'o_level_confirmed' => $request->boolean('o_level_confirmed'),
                'previous_level_confirmed' => $request->boolean('previous_level_confirmed'),
                'read_write_confirmed' => $request->boolean('read_write_confirmed'),
                'application_fee_confirmed' => $request->boolean('application_fee_confirmed'),
                'proof_of_payment_confirmed' => $request->boolean('proof_of_payment_confirmed'),
                'passport_photos_confirmed' => $request->boolean('passport_photos_confirmed'),
                'original_birth_certificate_confirmed' => $request->boolean('original_birth_certificate_confirmed'),
                'original_national_identity_confirmed' => $request->boolean('original_national_identity_confirmed'),
                'original_education_certificates_confirmed' => $request->boolean('original_education_certificates_confirmed'),
            ]);
            // Now check actual stored values, not request only
            if (
                $entry->attributes['identity_confirmed'] &&
                $entry->attributes['disability_confirmed'] &&
                $entry->attributes['names_confirmed']
            ) {
                $entry->type = ($type === 'provisional' || $type === 'waiting') ? ClassListTypeEnum::VERIFIED->value : ClassListTypeEnum::FINAL->value;
                $entry->save();
                // Generate student number
                $studentNumber = EnrolmentHelper::resolveStudentNumber($studentApplication);
                $student = $studentApplication->student;
                $student->fresh()->update([
                    'student_number' => $studentNumber,
                    'student_number_generated' => true,
                ]);
                // Change student application status to accepted
                $step = WorkflowStep::where('slug', WorkflowStepEnum::ACCEPTED->slug())->first();
                // Send email with offer letter
                $user = $student->user;
                if ($type === 'provisional' || $type === 'waiting') {
                    SendOfferLetterJob::dispatch($user->full_name, $user->email, $studentApplication->id)->withoutDelay();
                    if (EnrolmentHelper::isEntryLevel($studentApplication)) {
                        EnrolmentHelper::rejectOtherApplications($studentApplication->student, $studentApplication);
                    }
                }
                if ($type === 'verified') {
                    $step = WorkflowStep::where('slug', WorkflowStepEnum::ENROLLED->slug())->first();
                    $this->createStudentEnrolment($studentApplication);
                }
                // Update step
                $departmentStep = DepartmentApplicationStep::where('institution_department_id', $studentApplication->institution_department_id)->where('workflow_step_id', $step->id)->first();
                $studentApplication->update(['department_application_step_id' => $departmentStep->id]);
                // Create notes / remarks
                if ($request->has('remarks') && ! empty($request->remarks)) {
                    $studentApplication->notes()->create(['title' => 'Application confirmation', 'body' => $request->remarks]);
                }
            }

            return back()->with('success', 'Class list entry updated successfully.');
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while updating class list entry. All changes have been rolled back.');
        }
    }

    private function createStudentEnrolment(StudentApplication $studentApplication): void
    {
        try {
            $enrolmentAttributes = $this->resolveStudentEnrolmentAttributes->resolve(
                (int) $studentApplication->student_id,
                (int) $studentApplication->id,
            );

            StudentEnrolment::query()->updateOrCreate(
                [
                    'student_id' => $studentApplication->student_id,
                    'student_application_id' => $studentApplication->id,
                    'institution_department_id' => $studentApplication->institution_department_id,
                    'department_level_id' => $studentApplication->department_level_id,
                    'department_course_id' => $studentApplication->department_course_id,
                    'academic_year_option_id' => $enrolmentAttributes['academic_year_option_id'],
                    'academic_calendar_id' => $enrolmentAttributes['academic_calendar_id'],
                    'mode_of_study_id' => $studentApplication->mode_of_study_id,
                ],
                [
                    'student_application_id' => $studentApplication->id,
                    'student_enrolment_status_id' => $enrolmentAttributes['student_enrolment_status_id'],
                    'mode_of_study_id' => $studentApplication->mode_of_study_id,
                ],
            );
        } catch (StudentEnrolmentResolutionException $exception) {
            logger()->warning('Class list update: student enrolment not created.', [
                'student_application_id' => (int) $studentApplication->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function rejectApplication(StudentApplication $studentApplication)
    {
        try {
            // get class list
            $entry = ClassList::where('student_application_id', $studentApplication->id)->first();
            if (! $entry) {
                return back()->with('error', 'Class list entry not found for the specified student program.');
            }
            $entry->type = ClassListTypeEnum::FAILED->value;
            $entry->save();
            // change student application status to rejected
            $step = WorkflowStep::where('slug', WorkflowStepEnum::REJECTED->slug())->first();
            $departmentStep = DepartmentApplicationStep::where('institution_department_id', $studentApplication->institution_department_id)->where('workflow_step_id', $step->id)->first();
            $studentApplication->update(['department_application_step_id' => $departmentStep->id]);

            return back()->with('success', 'Class list entry updated successfully.');
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while updating class list entry. All changes have been rolled back.');
        }
    }

    public function verify(StudentApplication $studentApplication)
    {
        $this->authorize('verify:class-lists');
        $nextTop = $this->getStudent($studentApplication);
        $student = $studentApplication->student;
        $otherApplications = $student->applications()
            ->where('id', '!=', $studentApplication->id)
            ->with(['institutionDepartment', 'departmentLevel.level', 'departmentCourse.course', 'intakePeriod', 'modeOfStudy', 'classList'])
            ->get();

        return Inertia::render('enrolments/ApplicationVerification', [
            'application' => EnrolmentResource::make($studentApplication),
            'nextTop' => ClassListNextTopResource::collection($nextTop),
            'otherApplications' => OtherApplicationResource::collection($otherApplications),
        ]);
    }

    public function confirm(StudentApplication $studentApplication)
    {
        $this->authorize('manage-final:class-lists');
        $nextTop = $this->getStudent($studentApplication);
        $department = $studentApplication->institutionDepartment->department->name ?? '';
        $modeOfStudy = $studentApplication->modeOfStudy->name ?? '';

        // Tuition Lookup
        $tuitionFeeType = FeeType::where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = FeeStructure::query()
            ->where('tenant_id', $studentApplication->tenant_id)
            ->where('level_id', $studentApplication->departmentLevel->level->id ?? null)
            ->where('mode_of_study_id', $studentApplication->modeOfStudy->id ?? null)
            ->where('fee_type_id', $tuitionFeeType->id)->first();

        $tuition = $feeStructure->local_fca_amount ?? 0;
        $autoCardFee = DepartmentHelper::requiredAutoCardFee($department);
        $partTimeLevy = DepartmentHelper::partTimeLevy($modeOfStudy);

        return Inertia::render('enrolments/ApplicationConfirmation', [
            'application' => EnrolmentResource::make($studentApplication),
            'nextTop' => ClassListNextTopResource::collection($nextTop),
            'tuition' => $tuition,
            'autoCardFee' => $autoCardFee,
            'partTimeLevy' => $partTimeLevy,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function classLists(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        Gate::any(['view:class-lists', 'manage-final:class-lists']) || abort(403);

        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->departmentEnrolmentService->extractFilters();

        // ------------------------------------------------------------
        // 1. Resolve static/cached data
        // ------------------------------------------------------------
        $intakePeriods = DropdownHelper::getIntakePeriods();
        $modesOfStudy = DropdownHelper::getModesOfStudy();

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

    public function getStudent(StudentApplication $studentApplication): Collection
    {
        $studentApplication->load([
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

        return DB::table('student_applications as sp')
            ->join('class_lists as cl', 'cl.student_application_id', '=', 'sp.id')
            ->join('students as st', 'st.id', '=', 'sp.student_id')
            ->join('users as us', 'us.id', '=', 'st.user_id')
            ->select('sp.id as application_id', 'us.first_name', 'us.middle_name', 'us.last_name')
            ->whereNotIn('sp.id', [$studentApplication->id])
            ->where('sp.institution_department_id', $studentApplication->institution_department_id)
            ->where('sp.department_level_id', $studentApplication->department_level_id)
            ->where('sp.department_course_id', $studentApplication->department_course_id)
            ->where('sp.intake_period_id', $studentApplication->intake_period_id)
            ->where('sp.mode_of_study_id', $studentApplication->mode_of_study_id)
            ->where('cl.type', $studentApplication->classList->type)
            ->take(5)
            ->get();
    }
}
