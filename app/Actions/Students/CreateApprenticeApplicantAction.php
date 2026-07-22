<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\Enums\Students\ApplicationTrackEnum;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentApprentice;
use App\Models\Users\User;
use App\Services\Students\ApplicationEligibilityService;
use RuntimeException;

class CreateApprenticeApplicantAction
{
    public function __construct(
        protected ApplicationEligibilityService $eligibility,
    ) {}

    public function execute(User $user, string $employer, string $apprenticeNumber): Student
    {
        $intakePeriod = $this->eligibility->resolveIntakeForTrack(ApplicationTrackEnum::Apprentice);

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

        return $student->refresh();
    }
}
