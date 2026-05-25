<?php

namespace App\Services;

use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DepartmentEnrolmentService
{
    public function queryEnrolments(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $intakePeriodId,
        int $modeOfStudyId,
        int $courseId,
        int $perPage = 1500
    ): array
    {
        // ------------------------------------------------------------
        // 1. Cached IDs
        // ------------------------------------------------------------
        $oLevelId = cache()->rememberForever('o_level_id', fn() => AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->value('id')
        );

        $applicationFeeId = cache()->rememberForever('application_fee_id', fn() => FeeType::where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->value('id')
        );

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
                'required_level_completed',
                'read_write_acknowledged',
                'offer_accepted',
            ])
            ->paginate($perPage);

        $studentPrograms = $paginator->getCollection();

        $studentIds = $studentPrograms->pluck('student_id')->unique();
        $userIds = $studentPrograms->pluck('student.user_id')->unique();
        $studentProgramIds = $studentPrograms->pluck('application_id')->unique();

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
        // 5.1 Preload class list membership
        // ------------------------------------------------------------
        $classLists = DB::table('class_lists')
            ->whereIn('student_program_id', $studentProgramIds)
            ->whereNull('deleted_at')
            ->select('student_program_id', 'type')
            ->get()
            ->keyBy('student_program_id');

        // ------------------------------------------------------------
        // 6. Transform students
        // ------------------------------------------------------------
        $studentPrograms->transform(function ($sp) use ($academicStats, $academicResults, $receipts, $classLists) {

            $student = $sp->student;
            $user = $student?->user;

            // Identity
            $sp->student_name = $user?->full_name ?? '---';
            $sp->email = $user?->email ?? '---';

            // Student info (safe)
            $sp->phone_number = $student?->contacts?->first()?->phone_number;
            $sp->student_number = $student?->student_number;
            $sp->disability_status = $student?->disability_status;
            $sp->gender = $student?->gender?->title;

            // Workflow
            $sp->workflow_step = $sp->departmentWorkflowStep?->workflowStep?->name;

            // Flags
            $sp->required_level_completed = (bool) $sp->required_level_completed;
            $sp->read_write_acknowledged = (bool) $sp->read_write_acknowledged;
            $sp->offer_accepted = (bool) $sp->offer_accepted;

            // Academic stats (only if student exists)
            $stats = $student ? $academicStats->get($student->id) : null;
            $sp->exam_sittings_count = $stats->exam_sittings_count ?? 0;
            $sp->first_exam_year = $stats->first_exam_year ?? null;

            // Receipt (only if user exists)
            $receipt = $user ? $receipts->get($user->id) : null;
            $sp->receipt_id = $receipt->receipt_id ?? null;
            $sp->receipt_amount = $receipt->receipt_amount ?? null;

            // Academic results
            $sp->academic_results = $student
                ? $academicResults->get($student->id, collect())
                : collect();

            // Class list
            $classList = $classLists->get($sp->application_id);
            $sp->in_class_list = (bool) $classList;
            $sp->class_list_type = $classList->type ?? null;

            return $sp;
        });

        /*$studentPrograms->transform(function ($sp) use ($academicStats, $academicResults, $receipts, $classLists) {

            $student = $sp->student;
            $user = $student?->user;

            $sp->student_name = $user ? "{$user?->full_name}" : "---";
            $sp->email = $user ? $user->email : "---";
            $sp->phone_number = $student->contacts->first()?->phone_number;
            $sp->student_number = $student->student_number;
            $sp->disability_status = $student->disability_status;
            $sp->gender = $student->gender->title ?? null;
            $sp->workflow_step = $sp->departmentWorkflowStep?->workflowStep?->name;
            $sp->application_date = $sp->application_date;
            $sp->required_level_completed = $sp->required_level_completed ?? false;
            $sp->read_write_acknowledged = $sp->read_write_acknowledged ?? false;
            $sp->offer_accepted = $sp->offer_accepted ?? false;

            // Academic stats
            $stats = $academicStats->get($student->id);
            $sp->exam_sittings_count = $stats->exam_sittings_count ?? 0;
            $sp->first_exam_year = $stats->first_exam_year ?? null;

            // Receipt info
            $receipt = $receipts->get($student->user_id);
            $sp->receipt_id = $receipt->receipt_id ?? null;
            $sp->receipt_amount = $receipt->receipt_amount ?? null;

            // Academic results
            $sp->academic_results = $academicResults->get($student->id, collect());

            // ✅ Class list check
            $classList = $classLists->get($sp->application_id);
            $sp->in_class_list = $classList ? true : false;
            $sp->class_list_type = $classList->type ?? null;

            return $sp;
        });*/

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

    public function queryClassLists(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $intakePeriodId,
        int $modeOfStudyId,
        int $courseId,
        int $perPage = 1500
    ): array
    {

        // ------------------------------------------------------------
        // 1. Subquery for latest student program per student
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
        // 2. Eager load all necessary relations
        // ------------------------------------------------------------
        $type = request('type', ClassListTypeEnum::PROVISIONAL->value);
        $paginator = StudentProgram::query()
            ->join('class_lists', 'class_lists.student_program_id', '=', 'student_programs.id')
            ->with([
                'student.user:id,first_name,last_name,email',
                'student.gender:id,title',
                'student.contacts' => fn($q) => $q->orderBy('created_at')->limit(1),
                'departmentWorkflowStep.workflowStep:id,name',
            ])
            ->whereIn('student_programs.id', $subQuery)
            ->whereIn('class_lists.type', [$type])
            ->select([
                'student_programs.id as application_id',
                'student_programs.student_id',
                'student_programs.department_application_step_id',
                'student_programs.application_tracking_number',
                'student_programs.created_at as application_date',
                'student_programs.required_level_completed',
                'student_programs.read_write_acknowledged',
                'student_programs.offer_accepted',
                'class_lists.type as class_list_type',
            ])
            ->orderBy('class_list_type')
            ->orderBy('class_lists.created_at')
            ->paginate($perPage);


        $studentPrograms = $paginator->getCollection();

        // ------------------------------------------------------------
        // 3. Transform students
        // ------------------------------------------------------------
        $oLevelId = cache()->rememberForever('o_level_id', fn() => AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->value('id')
        );
        $studentIds = $studentPrograms->pluck('student_id')->unique();

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
        // 4. Preload receipts in bulk
        // ------------------------------------------------------------
        $userIds = $studentPrograms->pluck('student.user_id')->unique();
        $applicationFeeId = cache()->rememberForever('application_fee_id', fn() => FeeType::where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->value('id')
        );
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
        // 5. Transform students
        // ------------------------------------------------------------
        $studentPrograms->transform(function ($sp) use ($academicResults, $receipts) {
            $student = $sp->student;
            $user = $student->user;

            $sp->student_name = "{$user->full_name}";
            $sp->email = $user->email;
            $sp->phone_number = $student->contacts->first()?->phone_number;
            $sp->student_number = $student->student_number;
            $sp->disability_status = $student->disability_status;
            $sp->gender = $student->gender->title ?? null;
            $sp->application_date = $sp->application_date;
            $sp->class_list_type = $sp->class_list_type ?? null;
            // Academic results
            $sp->academic_results = $academicResults->get($student->id, collect());

            // Receipt info
            $receipt = $receipts->get($student->user_id);
            $sp->receipt_id = $receipt->receipt_id ?? null;
            $sp->receipt_amount = $receipt->receipt_amount ?? null;

            return $sp;
        });

        // ------------------------------------------------------------
        // 6. Group students by priority
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
        // 7. Return
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

    public function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int)request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int)request('mode_of_study_id') : null;
        $courseId = request('department_course_id') > 0 ? (int)request('department_course_id') : null;

        return [$intakePeriodId, $modeOfStudyId, $courseId];
    }

    public function getClassSize(InstitutionDepartment $institutionDepartment, $departmentLevelId, $departmentCourseId, $intakePeriodId, $modeOfStudyId): int
    {
        return $institutionDepartment->intakeClassSizes()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->where('intake_period_id', $intakePeriodId)
            ->where('mode_of_study_id', $modeOfStudyId)->pluck('class_size')->first() ?? 0;
    }
}
