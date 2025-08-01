<?php

namespace Database\Seeders\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Enums\Acl\RoleEnum;
use App\Enums\Institution\CourseEnum;
use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\AcademicLevelEnum;
use App\Enums\Shared\IdTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Shared\AcademicLevel;
use App\Models\Shared\Contact;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Race;
use App\Models\Shared\Title;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class StudentApplicationSeeder extends Seeder
{
    private function getTenantId(): int
    {
        return TenantEnum::HARARE_POLY->id();
    }

    public function run(): void
    {
        // create users
        $users = User::factory()->count(100)->create(['tenant_id' => $this->getTenantId(), 'password' => 'Student123!']);
        $intakePeriod = IntakePeriod::orderBy('end_date', 'DESC')->first();
        $genderIds = Gender::all()->pluck('id')->toArray();
        $titleIds = Title::all()->pluck('id')->toArray();
        $maritalStatusIds = MaritalStatus::all()->pluck('id')->toArray();
        $raceIds = Race::all()->pluck('id')->toArray();

        if (!$intakePeriod) {
            $intakePeriod = IntakePeriod::create(['name' => 'Default Intake Period', 'start_date' => now()->subMonth(),
                'end_date' => now()->addMonth(), 'is_active' => 1, 'tenant_id' => $this->getTenantId()]);
        }
        # date of birth range for students
        $dateOfBirthStart = Carbon::now()->subYears(70);
        $dateOfBirthEnd = Carbon::now()->subYears(16);

        # department, level, course
        $institutionDepartmentId = $this->getInstitutionDepartmentId();
        $departmentLevelId = $this->getDepartmentLevelId($institutionDepartmentId);
        $departmentCourseId = $this->getNcInformationTechnologyCourseId($institutionDepartmentId);
        # o-level
        $oLevel = AcademicLevel::where('name', AcademicLevelEnum::SECONDARY_SCHOOL->value)->first();
        foreach ($users as $user) {
            $user->assignRole(RoleEnum::STUDENT);
            $student = Student::create([
                'tenant_id' => $this->getTenantId(),
                'user_id' => $user->id,
                'title_id' => fake()->randomElement($titleIds),
                'gender_id' => fake()->randomElement($genderIds),
                'marital_status_id' => fake()->randomElement($maritalStatusIds),
                'race_id' => fake()->randomElement($raceIds),
                'id_type_id' => IdTypeEnum::ZIMBABWEAN_ID_NUMBER->id(),
                'id_number' => strtoupper(fake()->unique()->bothify('##-######?##')),
                'passport_number' => null,
                'country_id' => null,
                'study_permit_number' => null,
                'date_of_birth' => Carbon::createFromTimestamp(rand($dateOfBirthStart->timestamp, $dateOfBirthEnd->timestamp))->format('Y-m-d'),
            ]);
            $this->saveProgram($student, $intakePeriod->id, $institutionDepartmentId, $departmentLevelId, $departmentCourseId);
            $this->saveContact($student);
            $this->saveAddress($student);
            $this->saveNextOfKin($student);
            $this->saveAcademicResults($student, $oLevel);
        }
    }

    private function saveProgram(Student $student, $intakePeriodId, $institutionDepartmentId, $departmentLevelId, $departmentCourseId): void
    {
        StudentProgram::create([
            'tenant_id' => $this->getTenantId(),
            'student_id' => $student->id,
            'institution_department_id' => $institutionDepartmentId,
            'department_level_id' => $departmentLevelId,
            'department_course_id' => $departmentCourseId,
            'intake_period_id' => $intakePeriodId,
        ]);
    }

    private function getInstitutionDepartmentId(): int
    {
        $itDepartment = DepartmentEnum::INFORMATION_COMMUNICATION_TECHNOLOGY->value;
        $itDepartmentId = Department::where('name', $itDepartment)->value('id');
        return InstitutionDepartment::where('department_id', $itDepartmentId)
            ->where('tenant_id', $this->getTenantId())
            ->value('id');
    }

    private function getDepartmentLevelId($institutionDepartmentId): int
    {
        $ncLevel = LevelEnum::NC->value;
        $ncLevelId = Level::where('name', $ncLevel)->value('id');
        return DepartmentLevel::where('level_id', $ncLevelId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->where('tenant_id', $this->getTenantId())
            ->value('id');
    }

    private function getNcInformationTechnologyCourseId($institutionDepartmentId): int
    {
        $itCourse = CourseEnum::IT->value;
        $itCourseId = Course::where('name', $itCourse)->value('id');
        return DepartmentCourse::where('course_id', $itCourseId)
            ->where('institution_department_id', $institutionDepartmentId)
            ->where('tenant_id', $this->getTenantId())
            ->value('id');
    }

    private function saveContact(Student $student): void
    {
        $student->contacts()->create([
            'tenant_id' => $this->getTenantId(),
            'name' => $student->user->full_name,
            'phone_number' => fake()->phoneNumber,
            'alt_phone_number' => fake()->phoneNumber,
            'email_address' => fake()->email,
            'alt_email_address' => fake()->email,
            'contact_is_main' => true,
        ]);
    }

    private function saveAddress(Student $student): void
    {
        $student->addresses()->create([
            'address_1' => fake()->buildingNumber,
            'address_2' => fake()->streetName,
            'address_3' => fake()->city,
            'address_4' => fake()->postcode(),
            'address_5' => null,
            'address_6' => null,
            'address_is_main' => true,
        ]);
    }

    private function saveNextOfKin(Student $student): void
    {
        $nextOfKin = $student->nextOfKins()->create([
            'tenant_id' => $this->getTenantId(),
            'name' => fake()->name,
            'relationship_id' => 1, // Assuming 1 is a valid relationship ID
        ]);
        if ($nextOfKin) {
            // next of in contact
            $nextOfKin->contacts()->create([
                'name' => $nextOfKin->name,
                'phone_number' => fake()->phoneNumber,
                'contact_is_main' => true,
            ]);
            // next of kin address
            $nextOfKin->addresses()->create([
                'address_1' => fake()->buildingNumber,
                'address_2' => fake()->streetName,
                'address_3' => fake()->city,
                'address_4' => fake()->postcode(),
                'address_is_main' => true,
            ]);
        }
    }

    private function saveAcademicResults(Student $student, AcademicLevel $level): void
    {
        if (!empty($mainSubjects) && is_array($mainSubjects)) {
            foreach ($mainSubjects as $subjectId => $gradeId) {
                $examSitting = $examSittings[$subjectId] ?? null;
                $examYear = $examYears[$subjectId] ?? null;
                $student->academicResults()->create([
                    'academic_level_id' => $level->id,
                    'subject_id' => $subjectId,
                    'exam_year' => $examYear,
                    'exam_sitting' => $examSitting,
                    'grade_id' => $gradeId,
                ]);
            }
        }
    }
}
