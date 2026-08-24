<?php

namespace App\Services;

use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\DropdownHelper;
use App\Helpers\Helper;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\FeeType;
use App\Models\Students\ApplicationFee;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentEnrolmentService
{
    public function queryEnrolments(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $intakePeriodId,
        int $modeOfStudyId,
        ?int $courseId = null,
        ?int $perPage = null,
        bool $includeOLevelResults = true,
    ): array {
        $perPage ??= (int) config('custom.system.class_list_page_size', 200);
        $oLevelId = $includeOLevelResults
            ? cache()->rememberForever('o_level_id', fn () => AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->value('id'))
            : null;

        $applicationFeeId = cache()->rememberForever('application_fee_id', fn () => FeeType::where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->value('id')
        );

        // ------------------------------------------------------------
        // 2. Subquery for latest student program per student
        // ------------------------------------------------------------
        $subQuery = StudentApplication::query()
            ->selectRaw('MAX(id) as id')
            ->where([
                'institution_department_id' => $institutionDepartmentId,
                'department_level_id' => $departmentLevelId,
                'intake_period_id' => $intakePeriodId,
                'mode_of_study_id' => $modeOfStudyId,
            ])
            ->when($courseId, fn ($query) => $query->where('department_course_id', $courseId))
            ->groupBy('student_id');

        // ------------------------------------------------------------
        // 3. Eager load all necessary relations (single coherent set for grouping)
        // ------------------------------------------------------------
        $studentApplications = StudentApplication::query()
            ->with([
                'student.user:id,first_name,last_name,email',
                'student.gender:id,title',
                'student.contacts' => fn ($q) => $q->orderBy('created_at')->limit(1),
                'workflowStep:id,name',
            ])
            ->whereIn('id', $subQuery)
            ->select([
                'id as application_id',
                'student_id',
                'workflow_step_id',
                'application_tracking_number',
                'created_at as application_date',
                'required_level_completed',
                'read_write_acknowledged',
                'offer_accepted',
            ])
            ->orderBy('created_at')
            ->limit($perPage)
            ->get();

        $total = StudentApplication::query()->whereIn('id', $subQuery)->count();

        $studentIds = $studentApplications->pluck('student_id')->unique();
        $userIds = $studentApplications->pluck('student.user_id')->unique();
        $studentApplicationIds = $studentApplications->pluck('application_id')->unique();

        // ------------------------------------------------------------
        // 4. Preload academic stats & results in bulk (O-level only when required)
        // ------------------------------------------------------------
        $academicStats = collect();
        $academicResults = collect();

        if ($includeOLevelResults && $oLevelId && $studentIds->isNotEmpty()) {
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
        }
        // ------------------------------------------------------------
        // 5. Preload receipts in bulk
        // ------------------------------------------------------------
        $receipts = $this->preloadApplicationFeeReceipts($userIds, $applicationFeeId, $intakePeriodId);

        // ------------------------------------------------------------
        // 5.1 Preload class list membership
        // ------------------------------------------------------------
        $classLists = DB::table('class_lists')
            ->whereIn('student_application_id', $studentApplicationIds)
            ->whereNull('deleted_at')
            ->select('student_application_id', 'type')
            ->get()
            ->keyBy('student_application_id');

        // ------------------------------------------------------------
        // 6. Transform students
        // ------------------------------------------------------------
        $studentApplications->transform(function ($sp) use ($academicStats, $academicResults, $receipts, $classLists) {

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
            $sp->workflow_step = $sp->workflowStep?->name;

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

        /*$studentApplications->transform(function ($sp) use ($academicStats, $academicResults, $receipts, $classLists) {

            $student = $sp->student;
            $user = $student?->user;

            $sp->student_name = $user ? "{$user?->full_name}" : "---";
            $sp->email = $user ? $user->email : "---";
            $sp->phone_number = $student->contacts->first()?->phone_number;
            $sp->student_number = $student->student_number;
            $sp->disability_status = $student->disability_status;
            $sp->gender = $student->gender->title ?? null;
            $sp->workflow_step = $sp->workflowStep?->name;
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
            'disabled' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) === 'yes')
                ->sortBy('student_name')
                ->values(),

            'females' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) !== 'yes' &&
                    strtolower((string) $sp->gender) === 'female')
                ->sortBy('student_name')
                ->values(),

            'males' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) !== 'yes' &&
                    strtolower((string) $sp->gender) === 'male')
                ->sortBy('student_name')
                ->values(),
        ];

        // ------------------------------------------------------------
        // 8. Return
        // ------------------------------------------------------------
        return [
            'pagination' => [
                'current_page' => 1,
                'last_page' => max((int) ceil($total / max($perPage, 1)), 1),
                'per_page' => $perPage,
                'total' => $total,
                'links' => [],
            ],
            'groups' => $grouped,
        ];
    }

    public function queryClassLists(
        int $institutionDepartmentId,
        int $departmentLevelId,
        int $intakePeriodId,
        int $modeOfStudyId,
        ?int $courseId = null,
        ?int $perPage = null
    ): array {
        $perPage ??= (int) config('custom.system.class_list_page_size', 200);

        // ------------------------------------------------------------
        // 1. Subquery for latest student program per student
        // ------------------------------------------------------------
        $subQuery = StudentApplication::query()
            ->selectRaw('MAX(id) as id')
            ->where([
                'institution_department_id' => $institutionDepartmentId,
                'department_level_id' => $departmentLevelId,
                'intake_period_id' => $intakePeriodId,
                'mode_of_study_id' => $modeOfStudyId,
            ])
            ->when($courseId, fn ($query) => $query->where('department_course_id', $courseId))
            ->groupBy('student_id');

        // ------------------------------------------------------------
        // 2. Eager load all necessary relations
        // ------------------------------------------------------------
        $type = request('type', ClassListTypeEnum::PROVISIONAL->value);
        $paginator = StudentApplication::query()
            ->join('class_lists', 'class_lists.student_application_id', '=', 'student_applications.id')
            ->with([
                'student.user:id,first_name,last_name,email',
                'student.gender:id,title',
                'student.contacts' => fn ($q) => $q->orderBy('created_at')->limit(1),
                'workflowStep:id,name',
            ])
            ->whereIn('student_applications.id', $subQuery)
            ->whereIn('class_lists.type', [$type])
            ->select([
                'student_applications.id as application_id',
                'student_applications.student_id',
                'student_applications.workflow_step_id',
                'student_applications.application_tracking_number',
                'student_applications.created_at as application_date',
                'student_applications.required_level_completed',
                'student_applications.read_write_acknowledged',
                'student_applications.offer_accepted',
                'class_lists.type as class_list_type',
            ])
            ->orderBy('class_list_type')
            ->orderBy('class_lists.created_at')
            ->paginate($perPage);

        $studentApplications = $paginator->getCollection();

        // ------------------------------------------------------------
        // 3. Transform students
        // ------------------------------------------------------------
        $oLevelId = cache()->rememberForever('o_level_id', fn () => AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->value('id')
        );
        $studentIds = $studentApplications->pluck('student_id')->unique();

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
        $userIds = $studentApplications->pluck('student.user_id')->unique();
        $applicationFeeId = cache()->rememberForever('application_fee_id', fn () => FeeType::where('slug', FeeTypeEnum::APPLICATION_FEE->slug())->value('id')
        );
        $receipts = $this->preloadApplicationFeeReceipts($userIds, $applicationFeeId, $intakePeriodId);

        // ------------------------------------------------------------
        // 5. Transform students
        // ------------------------------------------------------------
        $studentApplications->transform(function ($sp) use ($academicResults, $receipts) {
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
            'disabled' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) === 'yes')
                ->sortBy('student_name')
                ->values(),

            'females' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) !== 'yes' &&
                    strtolower((string) $sp->gender) === 'female')
                ->sortBy('student_name')
                ->values(),

            'males' => $studentApplications
                ->filter(fn ($sp) => strtolower((string) $sp->disability_status) !== 'yes' &&
                    strtolower((string) $sp->gender) === 'male')
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

    /**
     * @return array{0: object, 1: object, 2: int|null, 3: Collection, 4: Collection}
     */
    public function resolveEnrolmentContext(): array
    {
        [$intakePeriodId, $modeOfStudyId, $courseId] = $this->extractFilters();

        $intakePeriods = DropdownHelper::getIntakePeriods();
        $modesOfStudy = DropdownHelper::getModesOfStudy();

        $intakePeriod = $this->matchById($intakePeriods, $intakePeriodId)
            ?? Helper::resolveIntakePeriod();

        $modeOfStudy = $this->matchById($modesOfStudy, $modeOfStudyId)
            ?? Helper::resolveModeOfStudy()
            ?? $modesOfStudy->first();

        abort_if($intakePeriod === null || $modeOfStudy === null, 404);

        return [$intakePeriod, $modeOfStudy, $courseId, $intakePeriods, $modesOfStudy];
    }

    public function extractFilters(): array
    {
        $intakePeriodId = request('intake_period_id') > 0 ? (int) request('intake_period_id') : null;
        $modeOfStudyId = request('mode_of_study_id') > 0 ? (int) request('mode_of_study_id') : null;
        $courseId = request('department_course_id') > 0 ? (int) request('department_course_id') : null;

        return [$intakePeriodId, $modeOfStudyId, $courseId];
    }

    /**
     * COUNT-based enrolment summaries for the department enrolments tab.
     *
     * When $type is set, counts are limited to class-list rows of that type.
     *
     * @return array{
     *     data: list<array{type: string, id: string, attributes: array<string, mixed>}>,
     *     meta: array{modeTotals: list<array{modeOfStudyId: int, count: int}>}
     * }
     */
    public function summariseDepartmentEnrolments(
        InstitutionDepartment $institutionDepartment,
        ?int $intakePeriodId,
        ?int $modeOfStudyId = null,
        ?string $type = null,
    ): array {
        $classListType = is_string($type) && $type !== '' ? $type : null;

        $base = StudentApplication::query()
            ->where('institution_department_id', $institutionDepartment->id)
            ->when($intakePeriodId, fn ($q) => $q->where('intake_period_id', $intakePeriodId))
            ->when(
                $classListType !== null,
                fn ($q) => $q->whereHas(
                    'classList',
                    fn ($classList) => $classList->where('type', $classListType),
                ),
            );

        $modeTotals = (clone $base)
            ->select('mode_of_study_id', DB::raw('COUNT(*) as aggregate'))
            ->whereNotNull('mode_of_study_id')
            ->groupBy('mode_of_study_id')
            ->get()
            ->map(fn ($row): array => [
                'modeOfStudyId' => (int) $row->mode_of_study_id,
                'count' => (int) $row->aggregate,
            ])
            ->values()
            ->all();

        $data = [];

        if ($modeOfStudyId !== null) {
            $rows = (clone $base)
                ->where('mode_of_study_id', $modeOfStudyId)
                ->whereNotNull('department_course_id')
                ->whereNotNull('department_level_id')
                ->select([
                    'department_course_id',
                    'department_level_id',
                    DB::raw('COUNT(*) as enrolments_count'),
                ])
                ->groupBy('department_course_id', 'department_level_id')
                ->get();

            $courseIds = $rows->pluck('department_course_id')->unique()->filter()->values();
            $levelIds = $rows->pluck('department_level_id')->unique()->filter()->values();

            $courses = DB::table('department_courses as dc')
                ->join('courses as c', 'c.id', '=', 'dc.course_id')
                ->whereIn('dc.id', $courseIds)
                ->select('dc.id', 'c.name')
                ->get()
                ->keyBy('id');

            $levels = DB::table('department_levels as dl')
                ->join('levels as l', 'l.id', '=', 'dl.level_id')
                ->whereIn('dl.id', $levelIds)
                ->select('dl.id', 'l.name')
                ->get()
                ->keyBy('id');

            $data = $rows->map(function ($row) use ($institutionDepartment, $modeOfStudyId, $courses, $levels): array {
                $courseId = (int) $row->department_course_id;
                $levelId = (int) $row->department_level_id;

                return [
                    'type' => 'department-enrolment-summaries',
                    'id' => "{$institutionDepartment->id}-{$courseId}-{$levelId}-{$modeOfStudyId}",
                    'attributes' => [
                        'institutionDepartmentId' => $institutionDepartment->id,
                        'departmentCourseId' => $courseId,
                        'courseName' => $courses->get($courseId)?->name,
                        'departmentLevelId' => $levelId,
                        'levelName' => $levels->get($levelId)?->name,
                        'enrolmentsCount' => (int) $row->enrolments_count,
                        'modeOfStudyId' => $modeOfStudyId,
                    ],
                ];
            })->values()->all();
        }

        return [
            'data' => $data,
            'meta' => [
                'modeTotals' => $modeTotals,
            ],
        ];
    }

    private function matchById(Collection $rows, ?int $id): ?object
    {
        if ($id === null) {
            return null;
        }

        $match = $rows->first(fn (mixed $row): bool => (int) data_get($row, 'id') === $id);

        return is_object($match) ? $match : null;
    }

    public function getClassSize(InstitutionDepartment $institutionDepartment, $departmentLevelId, $departmentCourseId, $intakePeriodId, $modeOfStudyId): int
    {
        return $institutionDepartment->intakeClassSizes()
            ->where('department_level_id', $departmentLevelId)
            ->where('department_course_id', $departmentCourseId)
            ->where('intake_period_id', $intakePeriodId)
            ->where('mode_of_study_id', $modeOfStudyId)->pluck('class_size')->first() ?? 0;
    }

    private function preloadApplicationFeeReceipts($userIds, ?int $applicationFeeId, int $intakePeriodId)
    {
        $userIds = collect($userIds)->filter()->unique()->values();

        if ($userIds->isEmpty() || $applicationFeeId === null) {
            return collect();
        }

        $applicationFees = ApplicationFee::query()
            ->whereIn('user_id', $userIds)
            ->where('intake_period_id', $intakePeriodId)
            ->get()
            ->keyBy('user_id');

        $legacyReceipts = Ledger::query()
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

        if ($applicationFees->isEmpty()) {
            return $legacyReceipts;
        }

        $applicationFeeReceipts = Ledger::query()
            ->whereIn('ledgerable_id', $applicationFees->pluck('id'))
            ->where('ledgerable_type', ApplicationFee::class)
            ->whereNull('deleted_at')
            ->where([
                'fee_type_id' => $applicationFeeId,
                'intake_period_id' => $intakePeriodId,
                'payment_status' => 'paid',
                'type' => 'receipt',
            ])
            ->get()
            ->keyBy('ledgerable_id');

        return $userIds->mapWithKeys(function (int $userId) use ($applicationFees, $legacyReceipts, $applicationFeeReceipts) {
            $applicationFee = $applicationFees->get($userId);

            if ($applicationFee !== null) {
                $receipt = $applicationFeeReceipts->get($applicationFee->id);

                if ($receipt !== null) {
                    return [$userId => (object) [
                        'user_id' => $userId,
                        'receipt_id' => $receipt->id,
                        'receipt_amount' => $receipt->amount,
                    ]];
                }
            }

            $legacy = $legacyReceipts->get($userId);

            return $legacy !== null ? [$userId => $legacy] : [];
        });
    }
}
