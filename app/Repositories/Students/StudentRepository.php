<?php

namespace App\Repositories\Students;

use App\DTO\Shared\AddressDto;
use App\DTO\Shared\ContactDto;
use App\DTO\Shared\NextOfKinDto;
use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\CreateStudentApplicationDto;
use App\DTO\Students\StudentApplicationDto;
use App\DTO\Students\UpdateStudentDto;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\GenderEnum;
use App\Helpers\Helper;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Shared\AcademicLevel;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Services\Enrollment\EnrollmentLookupService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentRepository extends BaseRepository implements IStudentRepository
{
    public function __construct(
        protected Student $student,
        protected IAddressRepository $addressRepository,
        protected IContactRepository $contactRepository,
        protected INextOfKinRepository $nextOfKinRepository,
        protected IStudentApplicationRepository $studentApplicationRepository,
    ) {
        parent::__construct($this->student);
    }

    public function paginateForIndex(array $filters = []): LengthAwarePaginator
    {
        $query = $this->baseIndexQuery();
        $this->applyIndexFilters($query, $filters);

        return $query
            ->latest('students.created_at')
            ->paginate($this->student->getPerPage())
            ->withQueryString();
    }

    public function statsForIndex(array $filters = []): array
    {
        $globalQuery = $this->baseStatsQuery();
        $this->applyIndexFilters($globalQuery, $this->globalStatsFilters($filters));

        $filteredQuery = $this->baseStatsQuery();
        $this->applyIndexFilters($filteredQuery, $filters);

        return [
            'global' => $this->aggregateGlobalStats($globalQuery),
            'filtered' => [
                'total' => $this->distinctStudentCount($filteredQuery),
            ],
        ];
    }

    public function queryForExport(array $filters = []): Builder
    {
        $exportFilters = collect($filters)->except(['name', 'search'])->all();
        $query = $this->baseIndexQuery();
        $this->applyIndexFilters($query, $exportFilters);

        return $query->latest('students.created_at');
    }

    private function baseIndexQuery(): Builder
    {
        return Student::query()
            ->with([
                'user',
                'gender',
                'latestEnrolment.institutionDepartment.department',
                'latestEnrolment.departmentLevel.level',
                'latestEnrolment.departmentCourse.course',
                'latestEnrolment.modeOfStudy',
                'enrolments.institutionDepartment.department',
                'enrolments.departmentLevel.level',
                'enrolments.departmentCourse.course',
                'enrolments.modeOfStudy',
                'enrolments.academicCalendarStudentEnrolment',
            ])
            ->join('student_enrolments', 'student_enrolments.student_id', '=', 'students.id')
            ->select('students.*')
            ->distinct();
    }

    private function baseStatsQuery(): Builder
    {
        return Student::query()
            ->join('student_enrolments', 'student_enrolments.student_id', '=', 'students.id')
            ->select('students.id');
    }

    /**
     * @return array{total: int, male: int, female: int, byLevel: list<array{id: int, name: string, count: int}>, byModeOfStudy: list<array{id: int, name: string, count: int}>}
     */
    private function aggregateGlobalStats(Builder $query): array
    {
        $total = $this->distinctStudentCount(clone $query);

        $male = $this->distinctStudentCount(
            (clone $query)->whereHas('gender', fn ($q) => $q->where('title', GenderEnum::MALE->value))
        );

        $female = $this->distinctStudentCount(
            (clone $query)->whereHas('gender', fn ($q) => $q->where('title', GenderEnum::FEMALE->value))
        );

        // Students with multiple enrolments may appear in more than one level/mode bucket.
        $byLevel = (clone $query)
            ->join('department_levels', 'student_enrolments.department_level_id', '=', 'department_levels.id')
            ->join('levels', 'department_levels.level_id', '=', 'levels.id')
            ->select('levels.id as id', 'levels.name as name')
            ->selectRaw('count(distinct students.id) as count')
            ->groupBy('levels.id', 'levels.name')
            ->orderBy('levels.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        $byModeOfStudy = (clone $query)
            ->join('mode_of_studies', 'student_enrolments.mode_of_study_id', '=', 'mode_of_studies.id')
            ->select('mode_of_studies.id as id', 'mode_of_studies.name as name')
            ->selectRaw('count(distinct students.id) as count')
            ->groupBy('mode_of_studies.id', 'mode_of_studies.name')
            ->orderBy('mode_of_studies.name')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();

        return [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'byLevel' => $byLevel,
            'byModeOfStudy' => $byModeOfStudy,
        ];
    }

    private function distinctStudentCount(Builder $query): int
    {
        return (int) (clone $query)->distinct()->count('students.id');
    }

    /**
     * @return array<string, mixed>
     */
    private function globalStatsFilters(array $filters): array
    {
        return [
            'department' => $filters['department'] ?? null,
            'with_trashed' => $filters['with_trashed'] ?? null,
        ];
    }

    private function applyIndexFilters(Builder $query, array $filters): void
    {
        // Search filter
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('student_number', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%");
            });
        }

        // Name filter
        if (! empty($filters['name'])) {
            $name = trim($filters['name']);

            $query->whereHas('user', function ($q) use ($name) {
                $q->where('first_name', 'like', "%{$name}%")
                    ->orWhere('middle_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%");
            });
        }

        // Department filter (institution department ids)
        $departmentIds = $this->intListFromFilter($filters['department'] ?? null);
        $isDepartmentUser = Helper::isDepartmentUser();
        $userDepartments = Helper::resolveUserDepartments() ?? [];

        if ($isDepartmentUser) {
            if ($userDepartments === []) {
                $query->whereRaw('1 = 0');
            } else {
                $departmentIds = $departmentIds !== []
                    ? array_values(array_intersect($departmentIds, $userDepartments))
                    : $userDepartments;

                if ($departmentIds === []) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('enrolments', function ($q) use ($departmentIds): void {
                        $q->whereIn('institution_department_id', $departmentIds);
                    });
                }
            }
        } elseif ($departmentIds !== []) {
            $query->whereHas('enrolments', function ($q) use ($departmentIds): void {
                $q->whereIn('institution_department_id', $departmentIds);
            });
        }

        // Level filter (canonical level ids on department_levels)
        $levelIds = $this->intListFromFilter($filters['level'] ?? null);
        if ($levelIds !== []) {
            $query->whereHas('enrolments.departmentLevel', function ($q) use ($levelIds): void {
                $q->whereIn('level_id', $levelIds);
            });
        }

        // Course filter (department_course ids)
        $courseIds = $this->intListFromFilter($filters['course'] ?? null);
        if ($courseIds !== []) {
            $query->whereHas('enrolments', function ($q) use ($courseIds): void {
                $q->whereIn('department_course_id', $courseIds);
            });
        }

        // Mode of study
        $modeIds = $this->intListFromFilter($filters['mode_of_study'] ?? null);
        if ($modeIds !== []) {
            $query->whereHas('enrolments', function ($q) use ($modeIds): void {
                $q->whereIn('mode_of_study_id', $modeIds);
            });
        }

        // Gender
        $gender = strtolower(trim((string) ($filters['gender'] ?? '')));
        if (in_array($gender, ['male', 'female'], true)) {
            $title = $gender === 'male' ? GenderEnum::MALE->value : GenderEnum::FEMALE->value;
            $query->whereHas('gender', fn ($q) => $q->where('title', $title));
        }

        // Trashed records
        if (! empty($filters['with_trashed'])) {
            $query->withTrashed();
        }
    }

    public function create(CreateApplicationDto|CreateStudentApplicationDto $dto): Model
    {
        $student = $this->student->create($this->createFields($dto))->refresh();
        $this->saveProgram($student, $dto);
        $this->saveContact($student, $dto);
        $this->saveAddress($student, $dto);
        $this->saveNextOfKin($student, $dto);
        $this->saveAcademicResults($student, $dto);

        return $student->refresh();
    }

    public function applyReturningApplication(Student $student, CreateApplicationDto $dto): StudentApplication
    {
        return DB::transaction(function () use ($student, $dto) {
            $user = $student->user;
            $user->update([
                'email' => $dto->email,
                'phone_number' => $dto->phone_number,
                'first_name' => $dto->first_name,
                'middle_name' => $dto->middle_name,
                'last_name' => $dto->last_name,
            ]);

            $student->update($this->createFields($dto));
            $this->upsertContact($student, $dto);
            $this->upsertAddress($student, $dto);
            $this->upsertNextOfKin($student, $dto);
            $this->syncAcademicResults($student, $dto);

            $programDto = new StudentApplicationDto(
                student_id: $student->id,
                mode_of_study_id: $dto->mode_of_study_id,
                institution_department_id: $dto->department_id,
                department_level_id: $dto->level_id,
                department_course_id: $dto->course_id,
                intake_period_id: $dto->intake_period_id,
                required_level_completed: $dto->required_level_completed,
                read_write_acknowledged: $dto->read_write_acknowledged,
            );

            return $this->studentApplicationRepository->create($programDto);
        });
    }

    /**
     * @throws Throwable
     */
    public function update(Student $student, UpdateStudentDto $dto)
    {
        return DB::transaction(function () use ($student, $dto) {
            $user = $student->user;

            $user->update([
                'email' => $dto->email ?? $user->email,
                'phone_number' => $dto->phone_number ?? $user->phone_number,
                'first_name' => $dto->first_name ?? $user->first_name,
                'middle_name' => $dto->middle_name ?? null,
                'last_name' => $dto->last_name ?? $user->last_name,
            ]);

            $student->update($this->updateFields($dto));

            return $student;
        });
    }

    public function allFilter($columns = ['*'], ?StudentFilter $filters = null)
    {
        return $this->student
            ->select($columns)
            ->filter($filters)
            ->orderBy('created_at')
            ->orderBy('deleted_at')
            ->paginate()
            ->withQueryString();
    }

    private function createFields(CreateApplicationDto|CreateStudentApplicationDto $dto): array
    {
        $cleanIdNumber = $dto->id_number
            ? EnrollmentLookupService::normalizeNationalId($dto->id_number)
            : null;

        return [
            'user_id' => $dto->user_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id ?? null,
            'id_type_id' => $dto->id_type_id ?? null,
            'id_number' => $cleanIdNumber,
            'passport_number' => $dto->passport_number ?? null,
            'country_id' => $dto->country_id ?? null,
            'study_permit_number' => $dto->study_permit_number ?? null,
            'required_exam_sitting_count' => $this->getRequiredExamSittingCount($dto->o_level_years ?? [], $dto->o_level_other_years ?? []),
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
            'disability_status' => $dto->disability_status,
        ];
    }

    private function updateFields(UpdateStudentDto $dto): array
    {
        return [
            'id_type_id' => $dto->id_type_id,
            'id_number' => $dto->id_number,
            'passport_number' => $dto->passport_number,
            'country_id' => $dto->country_id,
            'date_of_birth' => Carbon::parse($dto->date_of_birth)->format('Y-m-d'),
            'marital_status_id' => $dto->marital_status_id,
            'race_id' => $dto->race_id,
            'title_id' => $dto->title_id,
            'gender_id' => $dto->gender_id,
            'religion_id' => $dto->religion_id,
            'denomination' => $dto->denomination,
            'height' => $dto->height,
            'weight' => $dto->weight,
            'study_permit_number' => $dto->study_permit_number,
            'disability_status' => $dto->disability_status,
        ];
    }

    private function saveProgram(Student $student, CreateApplicationDto|CreateStudentApplicationDto $dto): void
    {
        $programDto = new StudentApplicationDto(
            student_id: $student->id,
            mode_of_study_id: $dto->mode_of_study_id,
            institution_department_id: $dto->department_id,
            department_level_id: $dto->level_id,
            department_course_id: $dto->course_id,
            intake_period_id: $dto->intake_period_id,
            required_level_completed: $dto->required_level_completed,
            read_write_acknowledged: $dto->read_write_acknowledged,
        );
        $this->studentApplicationRepository->create($programDto);
    }

    private function saveContact(Student $student, CreateApplicationDto|CreateStudentApplicationDto $dto): void
    {
        $nameParts = array_filter([
            $dto->first_name,
            $dto->middle_name,
            $dto->last_name,
        ]);
        $contactDto = new ContactDto(
            name: implode(' ', $nameParts),
            phone_number: $dto->phone_number,
            alt_phone_number: $dto->alt_phone_number,
            email_address: $dto->email,
            alt_email_address: null,
            contact_is_main: 1,
        );
        $this->contactRepository->create($student, $contactDto);
    }

    private function saveAddress(Student $student, CreateApplicationDto|CreateStudentApplicationDto $dto): void
    {
        $addressDto = new AddressDto(
            address_1: $dto->address_1,
            address_2: $dto->address_2,
            address_3: $dto->address_3,
            address_4: $dto->address_4,
            address_5: null,
            address_6: null,
            address_is_main: 1,
        );
        $this->addressRepository->create($student, $addressDto);
    }

    private function saveNextOfKin(Student $student, CreateApplicationDto|CreateStudentApplicationDto $dto): void
    {
        $nextOfKinDto = new NextOfKinDto(
            name: $dto->next_of_kin_name,
            relationship_id: $dto->relationship_id,
            phone_number: $dto->next_of_kin_phone_number,
            address_1: $dto->next_of_kin_address_1,
            address_2: $dto->next_of_kin_address_2,
            address_3: $dto->next_of_kin_address_3,
            address_4: $dto->next_of_kin_address_4,
        );
        $this->nextOfKinRepository->create($student, $nextOfKinDto);
    }

    private function saveAcademicResults(Student $student, CreateApplicationDto|CreateStudentApplicationDto $dto): void
    {
        $this->syncAcademicResults($student, $dto, creating: true);
    }

    private function upsertContact(Student $student, CreateApplicationDto $dto): void
    {
        $nameParts = array_filter([$dto->first_name, $dto->middle_name, $dto->last_name]);
        $contactDto = new ContactDto(
            name: implode(' ', $nameParts),
            phone_number: $dto->phone_number,
            alt_phone_number: $dto->alt_phone_number,
            email_address: $dto->email,
            alt_email_address: null,
            contact_is_main: 1,
        );
        $existing = $student->contacts()->where('contact_is_main', true)->first()
            ?? $student->contacts()->first();

        if ($existing !== null) {
            $this->contactRepository->update($existing, $contactDto);
        } else {
            $this->contactRepository->create($student, $contactDto);
        }
    }

    private function upsertAddress(Student $student, CreateApplicationDto $dto): void
    {
        $addressDto = new AddressDto(
            address_1: $dto->address_1,
            address_2: $dto->address_2,
            address_3: $dto->address_3,
            address_4: $dto->address_4,
            address_5: null,
            address_6: null,
            address_is_main: 1,
        );
        $existing = $student->addresses()->where('address_is_main', true)->first()
            ?? $student->addresses()->first();

        if ($existing !== null) {
            $this->addressRepository->update($existing, $addressDto);
        } else {
            $this->addressRepository->create($student, $addressDto);
        }
    }

    private function upsertNextOfKin(Student $student, CreateApplicationDto $dto): void
    {
        $nextOfKinDto = new NextOfKinDto(
            name: $dto->next_of_kin_name,
            relationship_id: $dto->relationship_id,
            phone_number: $dto->next_of_kin_phone_number,
            address_1: $dto->next_of_kin_address_1,
            address_2: $dto->next_of_kin_address_2,
            address_3: $dto->next_of_kin_address_3,
            address_4: $dto->next_of_kin_address_4,
        );
        $existing = $student->nextOfKins()->first();

        if ($existing !== null) {
            $this->nextOfKinRepository->update($existing, $nextOfKinDto);
        } else {
            $this->nextOfKinRepository->create($student, $nextOfKinDto);
        }
    }

    private function syncAcademicResults(
        Student $student,
        CreateApplicationDto|CreateStudentApplicationDto $dto,
        bool $creating = false,
    ): void {
        $mainSubjects = $dto->o_level_subject_ids;
        $examSittings = $dto->o_level_sittings;
        $examYears = $dto->o_level_years;
        $otherSubjects = $dto->o_level_other_subject_ids;
        $otherGrades = $dto->o_level_other_grade_ids;
        $otherExamYears = $dto->o_level_other_years;
        $otherSittings = $dto->o_level_other_sittings;
        $level = AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->first();

        if (! empty($mainSubjects) && is_array($mainSubjects)) {
            foreach ($mainSubjects as $subjectId => $gradeId) {
                $examSitting = $examSittings[$subjectId] ?? null;
                $examYear = $examYears[$subjectId] ?? null;
                $attributes = [
                    'exam_year' => $examYear,
                    'exam_sitting' => is_array($examSitting) ? ($examSitting['value'] ?? null) : $examSitting,
                    'grade_id' => $gradeId,
                ];

                if ($creating) {
                    $student->oLevelResults()->create(array_merge([
                        'academic_level_id' => $level->id,
                        'subject_id' => $subjectId,
                    ], $attributes));
                } else {
                    $student->oLevelResults()->updateOrCreate(
                        ['academic_level_id' => $level->id, 'subject_id' => $subjectId],
                        $attributes,
                    );
                }
            }
        }

        if (! empty($otherSubjects) && is_array($otherSubjects)) {
            foreach ($otherSubjects as $key => $subject) {
                $subjectId = is_array($subject) ? ($subject['value'] ?? null) : $subject;
                $otherGrade = $otherGrades[$key] ?? null;
                $otherSitting = $otherSittings[$key] ?? null;
                $otherExamYear = $otherExamYears[$key] ?? null;

                if (! $subjectId) {
                    continue;
                }

                $attributes = [
                    'exam_year' => $otherExamYear,
                    'exam_sitting' => is_array($otherSitting) ? ($otherSitting['value'] ?? null) : $otherSitting,
                    'grade_id' => $otherGrade,
                ];

                if ($creating) {
                    $student->oLevelResults()->create(array_merge([
                        'academic_level_id' => $level->id,
                        'subject_id' => $subjectId,
                    ], $attributes));
                } else {
                    $student->oLevelResults()->updateOrCreate(
                        ['academic_level_id' => $level->id, 'subject_id' => $subjectId],
                        $attributes,
                    );
                }
            }
        }
    }

    private function getRequiredExamSittingCount($examYears, $otherExamYears): int
    {
        if (empty($examYears) && $otherExamYears) {
            return 0;
        }
        $uniqueExamYears = array_values(
            array_unique(
                array_merge(
                    array_values($examYears ?? []),
                    array_values($otherExamYears ?? [])
                )
            )
        );

        return count($uniqueExamYears);
    }

    /**
     * @return list<int>
     */
    private function intListFromFilter(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $ids = [];
        foreach ($values as $v) {
            $i = (int) $v;
            if ($i > 0) {
                $ids[] = $i;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private function calendarTypeListFromFilter(mixed $value): array
    {
        if ($value === null || $value === '' || $value === []) {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $allowed = array_map(static fn (AcademicCalendarTypeEnum $e): string => $e->value, AcademicCalendarTypeEnum::cases());
        $out = [];
        foreach ($values as $v) {
            $s = is_string($v) ? trim($v) : (string) $v;
            if ($s !== '' && in_array($s, $allowed, true)) {
                $out[] = $s;
            }
        }

        return array_values(array_unique($out));
    }
}
