<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Shared\Gender;
use App\Models\Shared\IdType;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;

if (! function_exists('createVerifiedStudentProgram')) {
    function createVerifiedStudentProgram(string $studentNumber): StudentProgram
    {
        $tenant = Tenant::query()->firstOrFail();
        $department = Department::factory()->create();
        $institutionDepartment = InstitutionDepartment::query()->create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'department_code' => 'ict',
            'description' => 'Department for bulk finalise command tests',
        ]);
        $course = Course::factory()->create();
        $departmentCourse = DepartmentCourse::query()->create([
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'course_id' => $course->id,
        ]);
        $level = Level::factory()->create(['name' => 'Level 1']);
        $departmentLevel = DepartmentLevel::query()->create([
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => $level->id,
        ]);
        $modeOfStudy = ModeOfStudy::query()->create(['name' => 'Full Time']);
        $intakePeriod = IntakePeriod::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Semester 1 2026',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->endOfMonth()->toDateString(),
        ]);
        $title = Title::query()->create(['name' => 'Mr']);
        $gender = Gender::query()->create(['title' => 'Male']);
        $maritalStatus = MaritalStatus::query()->create(['title' => 'Single']);
        $idType = IdType::query()->create(['name' => 'National ID']);
        $studentUser = User::factory()->create([
            'tenant_id' => $tenant->id,
            'first_name' => 'Test',
            'middle_name' => 'Bulk',
            'last_name' => 'Student',
        ]);
        $student = Student::query()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $studentUser->id,
            'title_id' => $title->id,
            'gender_id' => $gender->id,
            'marital_status_id' => $maritalStatus->id,
            'id_type_id' => $idType->id,
            'id_number' => '63-000000A00',
            'student_number' => $studentNumber,
            'date_of_birth' => '2001-01-01',
        ]);

        $studentProgram = StudentProgram::query()->create([
            'tenant_id' => $tenant->id,
            'student_id' => $student->id,
            'institution_department_id' => $institutionDepartment->id,
            'department_level_id' => $departmentLevel->id,
            'department_course_id' => $departmentCourse->id,
            'intake_period_id' => $intakePeriod->id,
            'mode_of_study_id' => $modeOfStudy->id,
            'application_tracking_number' => 'APP-'.strtoupper(str()->random(8)),
            'program_status_id' => ClassListTypeEnum::VERIFIED->value,
        ]);

        ClassList::query()->create([
            'tenant_id' => $tenant->id,
            'student_program_id' => $studentProgram->id,
            'type' => ClassListTypeEnum::VERIFIED->value,
            'attributes' => [],
        ]);

        return $studentProgram;
    }
}

if (! function_exists('createBankCreditReceipt')) {
    function createBankCreditReceipt(string $studentNumber, string $transactionDate, string $transactionId): void
    {
        ZBBankStatement::query()->create([
            'tran_number_asc' => 'T-ASC-'.$transactionId,
            'tran_number_desc' => 'T-DESC-'.$transactionId,
            'transaction_id' => $transactionId,
            'transaction_sr_id' => 'TSR-'.$transactionId,
            'transaction_date' => $transactionDate,
            'debit_credit_flag' => 'C',
            'narration' => "Fees payment {$studentNumber}",
        ]);
    }
}

if (! function_exists('createEnrolledDepartmentStep')) {
    function createEnrolledDepartmentStep(StudentProgram $studentProgram): DepartmentApplicationStep
    {
        $step = WorkflowStep::query()->firstOrCreate(
            ['slug' => WorkflowStepEnum::ENROLLED->slug()],
            [
                'name' => WorkflowStepEnum::ENROLLED->name(),
                'description' => WorkflowStepEnum::ENROLLED->description(),
                'position' => WorkflowStepEnum::ENROLLED->position(),
            ]
        );

        return DepartmentApplicationStep::query()->create([
            'tenant_id' => $studentProgram->tenant_id,
            'institution_department_id' => $studentProgram->institution_department_id,
            'workflow_step_id' => $step->id,
            'position' => $step->position,
        ]);
    }
}
