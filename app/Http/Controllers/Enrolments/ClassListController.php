<?php

namespace App\Http\Controllers\Enrolments;

use App\Actions\Students\UpsertYearStudentEnrolmentAction;
use App\DTO\Enrolments\ClassListDto;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\DepartmentHelper;
use App\Helpers\EnrolmentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Enrolments\AddToClassListRequest;
use App\Http\Requests\Enrolments\BulkAddToClassListRequest;
use App\Http\Requests\Enrolments\ClassListRequest;
use App\Http\Requests\Enrolments\PurgeClassListRequest;
use App\Http\Requests\Enrolments\TransitionClassListRequest;
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
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Shared\FeeType;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use App\Repositories\Institution\interface\IClassListRepository;
use App\Services\DepartmentEnrolmentService;
use App\Services\Enrolments\ClassListTransitionService;
use App\Services\Students\StudentIdNumberValidationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ClassListController extends Controller
{
    public function __construct(
        protected IClassListRepository $repository,
        protected DepartmentEnrolmentService $departmentEnrolmentService,
        protected UpsertYearStudentEnrolmentAction $upsertYearStudentEnrolment,
        protected ClassListTransitionService $classListTransitionService,
        protected StudentIdNumberValidationService $studentIdNumberValidationService,
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

    public function addToClassList(StudentApplication $studentApplication, AddToClassListRequest $request): RedirectResponse
    {
        try {
            $result = $this->classListTransitionService->add(
                applicationIds: [$studentApplication->id],
                type: (string) $request->input('type'),
                actor: $request->user(),
                note: $request->input('note'),
                bypassRanking: $request->boolean('bypass_ranking'),
                context: $request->context(),
            );

            if ($result['added'] === 0) {
                return back()->with('success', 'Application is already on a class list.');
            }

            return back()->with('success', 'Application added to the class list.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while creating class lists. All changes have been rolled back.');
        }
    }

    public function bulkAdd(BulkAddToClassListRequest $request): RedirectResponse
    {
        try {
            $provisionalIds = array_values(array_map('intval', $request->input('application_ids', [])));
            $waitingIds = array_values(array_map('intval', $request->input('waiting_application_ids', [])));
            $note = $request->input('note');
            $bypassRanking = $request->boolean('bypass_ranking');
            $context = $request->context();
            $actor = $request->user();

            $added = 0;

            if ($provisionalIds !== []) {
                $result = $this->classListTransitionService->add(
                    applicationIds: $provisionalIds,
                    type: (string) $request->input('type', ClassListTypeEnum::PROVISIONAL->value),
                    actor: $actor,
                    note: $note,
                    bypassRanking: $bypassRanking,
                    context: $context,
                );
                $added += $result['added'];
            }

            if ($waitingIds !== []) {
                $result = $this->classListTransitionService->add(
                    applicationIds: $waitingIds,
                    type: ClassListTypeEnum::WAITING->value,
                    actor: $actor,
                    note: $note,
                    bypassRanking: $bypassRanking,
                    context: $context,
                );
                $added += $result['added'];
            }

            if ($added === 0) {
                return back()->with('success', 'No new applications to add; selected rows are already on a class list.');
            }

            return back()->with('success', $added.' application(s) added to the class list.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while adding applications to the class list.');
        }
    }

    public function transition(TransitionClassListRequest $request): RedirectResponse
    {
        try {
            $changed = $this->classListTransitionService->transition(
                applicationIds: $request->input('application_ids', []),
                toType: (string) $request->input('to_type'),
                actor: $request->user(),
                note: $request->input('note'),
                bypassRanking: $request->boolean('bypass_ranking'),
                context: $request->context(),
            );

            return back()->with('success', $changed.' class list entr'.($changed === 1 ? 'y' : 'ies').' updated.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while updating class list status.');
        }
    }

    public function purge(PurgeClassListRequest $request): RedirectResponse
    {
        try {
            $purged = $this->classListTransitionService->purge(
                applicationIds: $request->input('application_ids', []),
                actor: $request->user(),
                note: (string) $request->input('note'),
                context: $request->context(),
            );

            if ($purged === 0) {
                return back()->with('error', 'No class list entries found for the selected applications.');
            }

            return back()->with('success', $purged.' class list entr'.($purged === 1 ? 'y' : 'ies').' permanently removed.');
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return back()->with('error', 'An error occurred while purging class list entries.');
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
            $isVerification = $type === 'provisional' || $type === 'waiting';
            $isConfirmation = $type === 'verified';

            $entry = ClassList::where('student_application_id', $studentApplication->id)->first();
            if (! $entry) {
                return back()->with('error', 'Class list entry not found for the specified student program.');
            }

            $attributes = array_merge($entry->attributes ?? [], [
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

            $entry->attributes = $attributes;
            $entry->save();

            if ($isVerification) {
                $student = $studentApplication->student;
                if ($student !== null && ! $this->studentIdNumberValidationService->hasValidZimbabweanId($student)) {
                    return back()->with('error', __('trans.enrollment_invalid_national_id'));
                }

                if (
                    ! $attributes['identity_confirmed']
                    || ! $attributes['disability_confirmed']
                    || ! $attributes['names_confirmed']
                ) {
                    return back()->with(
                        'error',
                        'Identity, disability, and names must be confirmed before verifying this student.'
                    );
                }
            } elseif ($isConfirmation) {
                if (
                    ! $attributes['proof_of_payment_confirmed']
                    || ! $attributes['passport_photos_confirmed']
                    || ! $attributes['original_birth_certificate_confirmed']
                    || ! $attributes['original_national_identity_confirmed']
                    || ! $attributes['original_education_certificates_confirmed']
                ) {
                    return back()->with(
                        'error',
                        'Proof of payment, passport photos, birth certificate, national identity, and education certificates must be confirmed before elevating this student to the final class list.'
                    );
                }
            } else {
                return back()->with('error', "Unsupported class list update type \"{$type}\".");
            }

            DB::transaction(function () use ($request, $studentApplication, $entry, $isVerification, $isConfirmation): void {
                $entry->type = $isVerification
                    ? ClassListTypeEnum::VERIFIED->value
                    : ClassListTypeEnum::FINAL->value;
                $entry->save();

                $studentNumber = EnrolmentHelper::resolveStudentNumber($studentApplication);
                $student = $studentApplication->student;
                $student->fresh()->update([
                    'student_number' => $studentNumber,
                    'student_number_generated' => true,
                ]);

                $workflowSlug = $isVerification
                    ? WorkflowStepEnum::ACCEPTED->slug()
                    : WorkflowStepEnum::ENROLLED->slug();
                $step = WorkflowStep::where('slug', $workflowSlug)->first();
                if ($step === null) {
                    throw new \RuntimeException("Workflow step \"{$workflowSlug}\" was not found.");
                }

                if ($isVerification) {
                    $user = $student->user;
                    SendOfferLetterJob::dispatch($user->full_name, $user->email, $studentApplication->id)->withoutDelay();
                    if (EnrolmentHelper::isEntryLevel($studentApplication)) {
                        EnrolmentHelper::rejectOtherApplications($studentApplication->student, $studentApplication);
                    }
                }

                if ($isConfirmation) {
                    $this->createStudentEnrolment($studentApplication);
                }

                $studentApplication->update(['workflow_step_id' => $step->id]);

                if ($request->filled('remarks')) {
                    $studentApplication->notes()->create([
                        'title' => 'Application confirmation',
                        'body' => $request->remarks,
                    ]);
                }
            });

            return back()->with('success', 'Class list entry updated successfully.');
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', $e->getMessage());
        }
    }

    private function createStudentEnrolment(StudentApplication $studentApplication): void
    {
        $this->upsertYearStudentEnrolment->execute($studentApplication);
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
            $studentApplication->update(['workflow_step_id' => $step->id]);

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
            ->with(['institutionDepartment.department', 'departmentLevel.level', 'departmentCourse.course', 'intakePeriod', 'modeOfStudy', 'classList'])
            ->get();

        return Inertia::render('enrolments/ApplicationVerification', [
            'application' => EnrolmentResource::make($studentApplication),
            'nextTop' => ClassListNextTopResource::collection($nextTop),
            'otherApplications' => OtherApplicationResource::collection($otherApplications),
        ]);
    }

    public function confirm(StudentApplication $studentApplication)
    {
        $this->authorize('confirm:class-lists');
        $nextTop = $this->getStudent($studentApplication);
        $queue = $this->resolveQueuePosition($studentApplication);
        $student = $studentApplication->student;
        $otherApplications = $student === null
            ? collect()
            : $student->applications()
                ->where('id', '!=', $studentApplication->id)
                ->with(['institutionDepartment.department', 'departmentLevel.level', 'departmentCourse.course', 'intakePeriod', 'modeOfStudy', 'classList'])
                ->get();
        $department = $studentApplication->institutionDepartment->department->name ?? '';
        $modeOfStudy = $studentApplication->modeOfStudy->name ?? '';

        // Tuition Lookup
        $tuitionFeeType = FeeType::where('name', FeeTypeEnum::TUITION_FEE->name())->first();
        $feeStructure = $tuitionFeeType === null
            ? null
            : FeeStructure::query()
                ->where('tenant_id', $studentApplication->tenant_id)
                ->where('level_id', $studentApplication->departmentLevel->level->id ?? null)
                ->where('mode_of_study_id', $studentApplication->modeOfStudy->id ?? null)
                ->where('fee_type_id', $tuitionFeeType->id)
                ->first();

        $tuition = $feeStructure->local_fca_amount ?? 0;
        $autoCardFee = DepartmentHelper::requiredAutoCardFee($department);
        $partTimeLevy = DepartmentHelper::partTimeLevy($modeOfStudy);

        return Inertia::render('enrolments/ApplicationConfirmation', [
            'application' => EnrolmentResource::make($studentApplication),
            'nextTop' => ClassListNextTopResource::collection($nextTop),
            'otherApplications' => OtherApplicationResource::collection($otherApplications),
            'tuition' => $tuition,
            'autoCardFee' => $autoCardFee,
            'partTimeLevy' => $partTimeLevy,
            'queue' => $queue,
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function classLists(InstitutionDepartment $institutionDepartment, DepartmentLevel $departmentLevel): Response
    {
        $this->authorizeClassListBrowse(request()->string('type')->toString() ?: null);

        [$intakePeriod, $modeOfStudy, $courseId, $intakePeriods, $modesOfStudy] = $this->departmentEnrolmentService->resolveEnrolmentContext();

        $departmentCourse = $courseId
            ? DepartmentCourse::with(['course'])->find($courseId)
            : null;

        // ------------------------------------------------------------
        // 2. Query enrolments efficiently
        // ------------------------------------------------------------
        $results = $this->departmentEnrolmentService->queryClassLists(
            $institutionDepartment->id,
            $departmentLevel->id,
            (int) $intakePeriod->id,
            (int) $modeOfStudy->id,
            $courseId
        );

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

    private function authorizeClassListBrowse(?string $type = null): void
    {
        $typedPermission = EnrolmentHelper::classListBrowsePermissionForType($type);
        if ($typedPermission === null) {
            abort(403);
        }

        $this->authorize($typedPermission);
    }

    public function getStudent(StudentApplication $studentApplication): Collection
    {
        $studentApplication->load([
            'workflowStep',
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

    /**
     * @return array{position: int, total: int}
     */
    private function resolveQueuePosition(StudentApplication $studentApplication): array
    {
        $studentApplication->loadMissing('classList');

        $classListType = $studentApplication->classList?->type;
        if ($classListType === null) {
            return ['position' => 1, 'total' => 1];
        }

        $applicationIds = DB::table('student_applications as sp')
            ->join('class_lists as cl', 'cl.student_application_id', '=', 'sp.id')
            ->where('sp.institution_department_id', $studentApplication->institution_department_id)
            ->where('sp.department_level_id', $studentApplication->department_level_id)
            ->where('sp.department_course_id', $studentApplication->department_course_id)
            ->where('sp.intake_period_id', $studentApplication->intake_period_id)
            ->where('sp.mode_of_study_id', $studentApplication->mode_of_study_id)
            ->where('cl.type', $classListType)
            ->orderBy('cl.created_at')
            ->orderBy('sp.id')
            ->pluck('sp.id');

        $total = $applicationIds->count();
        $position = $applicationIds->search($studentApplication->id);

        return [
            'position' => $position === false ? 1 : ((int) $position + 1),
            'total' => max($total, 1),
        ];
    }
}
