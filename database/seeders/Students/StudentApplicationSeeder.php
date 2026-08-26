<?php

declare(strict_types=1);

namespace Database\Seeders\Students;

use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\GradeEnum;
use App\Enums\Institution\SubjectEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\RelationshipEnum;
use App\Enums\Shared\TenantEnum;
use App\Helpers\Helper;
use App\Helpers\WorkflowHelper;
use App\Models\Applications\ApplicationOfferingDepartment;
use App\Models\Institution\Grade;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Subject;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Race;
use App\Models\Shared\Relationship;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\IntakePeriodResolver;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class StudentApplicationSeeder extends Seeder
{
    public int $applicationsPerDepartment = 200;

    /**
     * Number of applications whose students are marked disabled.
     * Defaults to ~20% of applicationsPerDepartment (at least 1 when count > 0).
     */
    public ?int $disabledApplications = null;

    public ?string $levelName = 'NC';

    public ?string $courseName = 'Orthopedic Technology';

    /**
     * @var list<DepartmentEnum>
     */
    private array $departments = [
        DepartmentEnum::SCIENCE_TECHNOLOGY,
    ];

    private function getTenantId(): int
    {
        return TenantEnum::HARARE_POLY->id();
    }

    /**
     * @throws Throwable
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $tenantId = $this->getTenantId();
            $intakePeriod = $this->resolveOpenIntakePeriod($tenantId);
            $lookups = $this->resolveLookups();
            $oLevel = AcademicLevel::query()
                ->where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)
                ->firstOrFail();
            $stepOne = WorkflowHelper::getStepByPosition(1);
            $dateOfBirthStart = Carbon::now()->subYears(70);
            $dateOfBirthEnd = Carbon::now()->subYears(16);
            $disabledCount = $this->resolveDisabledCount();

            $this->command?->info(sprintf(
                'Using intake period "%s" (id %d) for seeded applications.',
                $intakePeriod->name,
                $intakePeriod->id,
            ));

            foreach ($this->departments as $departmentEnum) {
                $institutionDepartment = $this->resolveInstitutionDepartment($departmentEnum, $tenantId);
                $programmeChoices = $this->flattenOfferedProgrammes($institutionDepartment);

                $this->command?->info(sprintf(
                    'Seeding %d application(s) for %s%s (%d matching programme choice(s), %d disabled).',
                    $this->applicationsPerDepartment,
                    $departmentEnum->value,
                    $this->describeProgrammeFilter(),
                    count($programmeChoices),
                    $disabledCount,
                ));

                for ($i = 0; $i < $this->applicationsPerDepartment; $i++) {
                    $choice = fake()->randomElement($programmeChoices);
                    $user = User::factory()->create([
                        'tenant_id' => $tenantId,
                        'password' => 'Student123!',
                    ]);
                    $user->assignRole(RoleEnum::STUDENT->name());

                    $disabilityStatus = $i < $disabledCount
                        ? DisabilityStatusEnum::YES->value
                        : fake()->randomElement([
                            DisabilityStatusEnum::NO->value,
                            DisabilityStatusEnum::PREFER_NOT_TO_SAY->value,
                        ]);

                    $student = Student::query()->create([
                        'tenant_id' => $tenantId,
                        'user_id' => $user->id,
                        'title_id' => fake()->randomElement($lookups['titleIds']),
                        'gender_id' => fake()->randomElement($lookups['genderIds']),
                        'marital_status_id' => fake()->randomElement($lookups['maritalStatusIds']),
                        'race_id' => fake()->randomElement($lookups['raceIds']),
                        'id_type_id' => $lookups['idTypeId'],
                        'id_number' => strtoupper(fake()->unique()->bothify('##-######?##')),
                        'passport_number' => null,
                        'country_id' => null,
                        'study_permit_number' => null,
                        'date_of_birth' => Carbon::createFromTimestamp(
                            random_int($dateOfBirthStart->timestamp, $dateOfBirthEnd->timestamp)
                        )->format('Y-m-d'),
                        'disability_status' => $disabilityStatus,
                    ]);

                    $application = $this->saveProgram(
                        $student,
                        $intakePeriod->id,
                        $choice,
                        $stepOne,
                    );

                    $student->update([
                        'student_number' => Helper::generateStudentNumber($application),
                    ]);

                    $this->saveContact($student);
                    $this->saveAddress($student);
                    $this->saveNextOfKin($student, $lookups['relationshipId']);
                    $this->saveAcademicResults($student, $oLevel, $lookups['gradeIds'], $lookups['subjectIds']);
                }
            }
        });
    }

    private function resolveDisabledCount(): int
    {
        if ($this->applicationsPerDepartment <= 0) {
            return 0;
        }

        if ($this->disabledApplications !== null) {
            return max(0, min($this->disabledApplications, $this->applicationsPerDepartment));
        }

        return max(1, (int) round($this->applicationsPerDepartment * 0.2));
    }

    private function describeProgrammeFilter(): string
    {
        $parts = array_filter([
            $this->levelName !== null ? 'level '.$this->levelName : null,
            $this->courseName !== null ? 'course '.$this->courseName : null,
        ]);

        return $parts === [] ? '' : ' ['.implode(', ', $parts).']';
    }

    private function resolveOpenIntakePeriod(int $tenantId): IntakePeriod
    {
        $resolver = app(IntakePeriodResolver::class);
        $activeIds = $resolver->activeIntakePeriodIds();

        // Prefer the current open regular (non-continuous) intake.
        if ($activeIds !== []) {
            $intakePeriod = IntakePeriod::query()
                ->where('tenant_id', $tenantId)
                ->whereIn('id', $activeIds)
                ->orderByDesc('end_date')
                ->first();

            if ($intakePeriod !== null) {
                return $intakePeriod;
            }
        }

        // For testing seeds, fall back to the latest regular intake even if closed/suspended.
        $intakePeriod = IntakePeriod::query()
            ->where('tenant_id', $tenantId)
            ->where('is_continuous', false)
            ->where('is_active', true)
            ->orderByDesc('end_date')
            ->first();

        if ($intakePeriod === null) {
            throw new RuntimeException(
                'No regular intake period found. Create a current intake before seeding applications.'
            );
        }

        return $intakePeriod;
    }

    /**
     * @return array{
     *     genderIds: list<int>,
     *     titleIds: list<int>,
     *     maritalStatusIds: list<int>,
     *     raceIds: list<int>,
     *     idTypeId: int,
     *     relationshipId: int,
     *     gradeIds: list<int>,
     *     subjectIds: array{core: list<int>, other: list<int>}
     * }
     */
    private function resolveLookups(): array
    {
        $genderIds = Gender::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $titleIds = Title::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $maritalStatusIds = MaritalStatus::query()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $raceIds = Race::query()->pluck('id')->map(fn ($id) => (int) $id)->all();

        if ($genderIds === [] || $titleIds === [] || $maritalStatusIds === [] || $raceIds === []) {
            throw new RuntimeException(
                'Missing shared lookup data (gender/title/marital status/race). Run DatabaseSeeder first.'
            );
        }

        $idTypeId = IdType::query()
            ->where('name', IdTypeEnum::ZIMBABWEAN_ID_NUMBER->value)
            ->value('id');

        if ($idTypeId === null) {
            throw new RuntimeException('Zimbabwean National ID type not found. Run IdTypeSeeder first.');
        }

        $relationshipId = Relationship::query()
            ->where('name', RelationshipEnum::PARENT->value)
            ->value('id');

        if ($relationshipId === null) {
            throw new RuntimeException(
                'Parent relationship not found. Run RelationshipsTableSeeder first.'
            );
        }

        $gradeIds = Grade::query()
            ->whereIn('name', [GradeEnum::A->value, GradeEnum::B->value, GradeEnum::C->value])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($gradeIds === []) {
            throw new RuntimeException('Grades A/B/C not found. Run GradesTableSeeder first.');
        }

        $coreSubjectIds = [
            $this->resolveSubjectId([SubjectEnum::ENGLISH->value]),
            $this->resolveSubjectId([SubjectEnum::MATHEMATICS->value]),
            $this->resolveSubjectId([
                SubjectEnum::INTEGRATED_SCIENCE->value,
                'Any Science Subject',
                'Combined Science',
                'Integrated Science',
            ]),
        ];
        $otherSubjectIds = [
            $this->resolveSubjectId([SubjectEnum::AGRICULTURE->value]),
            $this->resolveSubjectId([SubjectEnum::BIBLE_KNOWLEDGE->value]),
        ];

        return [
            'genderIds' => $genderIds,
            'titleIds' => $titleIds,
            'maritalStatusIds' => $maritalStatusIds,
            'raceIds' => $raceIds,
            'idTypeId' => (int) $idTypeId,
            'relationshipId' => (int) $relationshipId,
            'gradeIds' => $gradeIds,
            'subjectIds' => [
                'core' => $coreSubjectIds,
                'other' => $otherSubjectIds,
            ],
        ];
    }

    /**
     * @param  list<string>  $candidateNames
     */
    private function resolveSubjectId(array $candidateNames): int
    {
        foreach ($candidateNames as $name) {
            $id = Subject::query()->where('name', $name)->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        foreach ($candidateNames as $name) {
            $id = Subject::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower(trim($name))])
                ->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        throw new RuntimeException(sprintf(
            'O-level subject not found (tried: %s). Run SubjectsTableSeeder or ensure catalogue subjects exist.',
            implode(', ', $candidateNames),
        ));
    }

    private function resolveInstitutionDepartment(DepartmentEnum $department, int $tenantId): InstitutionDepartment
    {
        $institutionDepartment = InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('department', fn ($query) => $query->where('name', $department->value))
            ->first();

        if ($institutionDepartment === null) {
            throw new RuntimeException(sprintf(
                'Institution department "%s" not found for tenant. Run InstitutionDepartmentsTableSeeder first.',
                $department->value,
            ));
        }

        return $institutionDepartment;
    }

    /**
     * @return list<array{
     *     institution_department_id: int,
     *     department_level_id: int,
     *     department_course_id: int,
     *     mode_of_study_id: int
     * }>
     */
    private function flattenOfferedProgrammes(InstitutionDepartment $institutionDepartment): array
    {
        $offeringDepartment = ApplicationOfferingDepartment::query()
            ->where('institution_department_id', $institutionDepartment->id)
            ->with([
                'levels.departmentLevel.level',
                'levels.courses.departmentCourse.course',
                'levels.courses.modes',
            ])
            ->first();

        if ($offeringDepartment === null) {
            throw new RuntimeException(sprintf(
                'No application offerings found for department "%s". Open courses in the application offerings catalogue first.',
                $institutionDepartment->department?->name ?? (string) $institutionDepartment->id,
            ));
        }

        $choices = [];

        foreach ($offeringDepartment->levels as $offeringLevel) {
            $levelName = $offeringLevel->departmentLevel?->level?->name;

            if ($this->levelName !== null && strcasecmp((string) $levelName, $this->levelName) !== 0) {
                continue;
            }

            foreach ($offeringLevel->courses as $offeringCourse) {
                $courseName = $offeringCourse->departmentCourse?->course?->name;

                if ($this->courseName !== null && strcasecmp((string) $courseName, $this->courseName) !== 0) {
                    continue;
                }

                foreach ($offeringCourse->modes as $offeringMode) {
                    $choices[] = [
                        'institution_department_id' => (int) $institutionDepartment->id,
                        'department_level_id' => (int) $offeringLevel->department_level_id,
                        'department_course_id' => (int) $offeringCourse->department_course_id,
                        'mode_of_study_id' => (int) $offeringMode->mode_of_study_id,
                    ];
                }
            }
        }

        if ($choices === []) {
            throw new RuntimeException(sprintf(
                'No matching open programme found for department "%s"%s.',
                $institutionDepartment->department?->name ?? (string) $institutionDepartment->id,
                $this->describeProgrammeFilter(),
            ));
        }

        return $choices;
    }

    /**
     * @param  array{
     *     institution_department_id: int,
     *     department_level_id: int,
     *     department_course_id: int,
     *     mode_of_study_id: int
     * }  $choice
     */
    private function saveProgram(
        Student $student,
        int $intakePeriodId,
        array $choice,
        ?WorkflowStep $step,
    ): StudentApplication {
        return StudentApplication::query()->create([
            'tenant_id' => $this->getTenantId(),
            'student_id' => $student->id,
            'institution_department_id' => $choice['institution_department_id'],
            'department_level_id' => $choice['department_level_id'],
            'department_course_id' => $choice['department_course_id'],
            'intake_period_id' => $intakePeriodId,
            'workflow_step_id' => $step?->id,
            'mode_of_study_id' => $choice['mode_of_study_id'],
        ]);
    }

    private function saveContact(Student $student): void
    {
        $student->contacts()->create([
            'tenant_id' => $this->getTenantId(),
            'name' => $student->user->full_name,
            'phone_number' => fake()->phoneNumber(),
            'alt_phone_number' => fake()->phoneNumber(),
            'email_address' => fake()->safeEmail(),
            'alt_email_address' => fake()->safeEmail(),
            'contact_is_main' => true,
        ]);
    }

    private function saveAddress(Student $student): void
    {
        $student->addresses()->create([
            'tenant_id' => $this->getTenantId(),
            'address_1' => fake()->buildingNumber(),
            'address_2' => fake()->streetName(),
            'address_3' => fake()->city(),
            'address_4' => fake()->postcode(),
            'address_5' => null,
            'address_6' => null,
            'address_is_main' => true,
        ]);
    }

    private function saveNextOfKin(Student $student, int $relationshipId): void
    {
        $nextOfKin = $student->nextOfKins()->make([
            'name' => fake()->name(),
            'relationship_id' => $relationshipId,
        ]);
        $nextOfKin->tenant_id = $this->getTenantId();
        $nextOfKin->save();

        $nextOfKin->contacts()->create([
            'tenant_id' => $this->getTenantId(),
            'name' => $nextOfKin->name,
            'phone_number' => fake()->phoneNumber(),
            'contact_is_main' => true,
        ]);

        $nextOfKin->addresses()->create([
            'tenant_id' => $this->getTenantId(),
            'address_1' => fake()->buildingNumber(),
            'address_2' => fake()->streetName(),
            'address_3' => fake()->city(),
            'address_4' => fake()->postcode(),
            'address_is_main' => true,
        ]);
    }

    /**
     * @param  list<int>  $gradeIds
     * @param  array{core: list<int>, other: list<int>}  $subjectIds
     */
    private function saveAcademicResults(
        Student $student,
        AcademicLevel $level,
        array $gradeIds,
        array $subjectIds,
    ): void {
        $examYear = 2024;
        $sitting = 'november';

        foreach ([...$subjectIds['core'], ...$subjectIds['other']] as $subjectId) {
            $student->oLevelResults()->create([
                'academic_level_id' => $level->id,
                'subject_id' => $subjectId,
                'exam_year' => $examYear,
                'exam_sitting' => $sitting,
                'grade_id' => fake()->randomElement($gradeIds),
            ]);
        }
    }
}
