<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\DTO\Students\StudentApplicationDto;
use App\Enums\Students\ApplicationTrackEnum;
use App\Helpers\WorkflowHelper;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Services\Students\ApplicationEligibilityService;
use App\Services\Students\ApplicationTrackSession;
use App\Services\Students\RegistrationProgrammeAvailabilityService;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateApprenticeApplicantAction
{
    public function __construct(
        protected ApplicationEligibilityService $eligibility,
        protected RegistrationProgrammeAvailabilityService $programmeAvailability,
        protected ApplicationTrackSession $trackSession,
        protected IStudentApplicationRepository $studentApplicationRepository,
    ) {}

    /**
     * @return array{student: Student, application: StudentApplication, programmeLabel: string}
     */
    public function execute(User $user, string $employer, string $apprenticeNumber): array
    {
        $intakePeriod = $this->eligibility->resolveIntakeForTrack(ApplicationTrackEnum::Apprentice);
        $programme = $this->resolveProgrammeSelection();

        $this->programmeAvailability->assertProgrammeSelection(
            ApplicationTrackEnum::Apprentice,
            $programme['levelId'],
            $programme['departmentId'],
            $programme['departmentLevelId'],
            $programme['courseId'],
            $programme['modeOfStudyId'],
        );

        $titleId = Title::query()->value('id');
        $genderId = Gender::query()->value('id');
        $maritalStatusId = MaritalStatus::query()->value('id');
        $idTypeId = session('registration.id_type_id') ?? IdType::query()->value('id');

        if ($titleId === null || $genderId === null || $maritalStatusId === null || $idTypeId === null) {
            throw new RuntimeException('Required reference data is missing for apprentice registration.');
        }

        $student = Student::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'tenant_id' => $user->tenant_id,
                'title_id' => $titleId,
                'gender_id' => $genderId,
                'marital_status_id' => $maritalStatusId,
                'id_type_id' => $idTypeId,
                'id_number' => session('registration.id_number'),
                'passport_number' => session('registration.passport_number'),
                'date_of_birth' => '2000-01-01',
            ],
        );

        StudentApprentice::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'calendar_year' => $intakePeriod->calendarYearInteger(),
            ],
            [
                'tenant_id' => $student->tenant_id,
                'employer' => $employer,
                'apprentice_number' => $apprenticeNumber,
            ],
        );

        $application = $this->studentApplicationRepository->create(new StudentApplicationDto(
            student_id: $student->id,
            mode_of_study_id: $programme['modeOfStudyId'],
            institution_department_id: $programme['departmentId'],
            department_level_id: $programme['departmentLevelId'],
            department_course_id: $programme['courseId'],
            intake_period_id: $intakePeriod->id,
            required_level_completed: null,
            read_write_acknowledged: null,
        ));

        $stepOne = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 1);
        $stepTwo = WorkflowHelper::getDepartmentApplicationStepByPosition($application->institution_department_id, 2);
        $application->update(['department_application_step_id' => $stepOne?->id ?? $stepTwo?->id]);

        if ($stepTwo !== null && $stepOne !== null) {
            $application->update(['department_application_step_id' => $stepTwo->id]);
        }

        return [
            'student' => $student->refresh(),
            'application' => $application->fresh(),
            'programmeLabel' => $this->formatProgrammeLabel($programme),
        ];
    }

    /**
     * @return array{
     *     levelId: int,
     *     departmentId: int,
     *     departmentLevelId: int,
     *     courseId: int,
     *     modeOfStudyId: int
     * }
     */
    private function resolveProgrammeSelection(): array
    {
        $levelId = $this->trackSession->levelId()
            ?? (Session::get('application.level_id') !== null ? (int) Session::get('application.level_id') : null);
        $departmentId = Session::get('application.department_id');
        $departmentLevelId = Session::get('application.department_level_id');
        $courseId = Session::get('application.course_id');
        $modeOfStudyId = Session::get('application.mode_of_study_id');

        if (
            $levelId === null
            || $departmentId === null
            || $departmentLevelId === null
            || $courseId === null
            || $modeOfStudyId === null
        ) {
            throw ValidationException::withMessages([
                'error' => __('trans.application_apprentice_programme_required'),
            ]);
        }

        return [
            'levelId' => (int) $levelId,
            'departmentId' => (int) $departmentId,
            'departmentLevelId' => (int) $departmentLevelId,
            'courseId' => (int) $courseId,
            'modeOfStudyId' => (int) $modeOfStudyId,
        ];
    }

    /**
     * @param  array{
     *     levelId: int,
     *     departmentId: int,
     *     departmentLevelId: int,
     *     courseId: int,
     *     modeOfStudyId: int
     * }  $programme
     */
    private function formatProgrammeLabel(array $programme): string
    {
        $department = InstitutionDepartment::query()
            ->with('department')
            ->find($programme['departmentId']);
        $departmentLevel = DepartmentLevel::query()
            ->with('level')
            ->find($programme['departmentLevelId']);
        $departmentCourse = DepartmentCourse::query()
            ->with('course')
            ->find($programme['courseId']);
        $mode = ModeOfStudy::query()->find($programme['modeOfStudyId']);

        return collect([
            $department?->department?->name ?? $department?->department_code,
            $departmentLevel?->level?->name,
            $departmentCourse?->course?->name,
            $mode?->name,
        ])->filter()->implode(' · ');
    }
}
