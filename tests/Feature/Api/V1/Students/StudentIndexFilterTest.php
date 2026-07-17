<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Staff;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentApprentice;
use App\Models\Users\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

function createStudentEnrolmentForProgram(StudentApplication $program): void
{
    $suffix = Str::lower(Str::random(6));

    $academicYearOption = AcademicYearOption::query()->create([
        'slug' => 'api-filter-'.$suffix,
        'name' => 'Semester '.$suffix,
        'description' => null,
    ]);

    $calendar = AcademicCalendar::query()->create([
        'calendar_year' => '2025/2026',
        'type' => 'semester',
        'opening_date' => '2026-01-01',
        'closing_date' => '2026-12-31',
    ]);

    $status = StudentEnrolmentStatus::query()->firstOrCreate(
        ['slug' => 'active'],
        ['name' => 'Active', 'description' => 'Test'],
    );

    StudentEnrolment::query()->create([
        'student_id' => $program->student_id,
        'student_application_id' => $program->id,
        'institution_department_id' => $program->institution_department_id,
        'department_level_id' => $program->department_level_id,
        'department_course_id' => $program->department_course_id,
        'academic_year_option_id' => $academicYearOption->id,
        'academic_calendar_id' => $calendar->id,
        'mode_of_study_id' => $program->mode_of_study_id,
        'student_enrolment_status_id' => $status->id,
    ]);
}

it('filters students by institution department id array', function (): void {
    $program = createVerifiedStudentApplication('STU-IDX-'.strtoupper(Str::random(4)));

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Sanctum::actingAs($user);

    createStudentEnrolmentForProgram($program);

    $deptId = (int) $program->institution_department_id;

    $matched = $this->getJson(route('v1.students.index').'?department[]='.$deptId);
    $matched->assertOk();
    $ids = collect($matched->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($ids)->toContain((int) $program->student_id);

    $empty = $this->getJson(route('v1.students.index').'?department[]=999999999');
    $empty->assertOk();
    expect($empty->json('data'))->toBe([]);
});

it('restricts students to the department user own departments', function (): void {
    $ownProgram = createVerifiedStudentApplication('STU-OWN-'.strtoupper(Str::random(4)));
    $otherProgram = createVerifiedStudentApplication('STU-OTH-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($ownProgram);
    createStudentEnrolmentForProgram($otherProgram);

    $departmentUser = User::factory()->create(['tenant_id' => $ownProgram->tenant_id]);
    $departmentUser->givePermissionTo('viewOnlyOwnDepartment:departments');

    $title = Title::query()->firstOrCreate(['name' => 'Mr']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male']);
    $maritalStatus = MaritalStatus::query()->firstOrCreate(['title' => 'Single']);

    $staff = Staff::query()->create([
        'tenant_id' => $ownProgram->tenant_id,
        'user_id' => $departmentUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $maritalStatus->id,
    ]);

    $staff->institutionDepartments()->attach($ownProgram->institution_department_id);

    Sanctum::actingAs($departmentUser);

    $response = $this->getJson(route('v1.students.index'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $ownProgram->student_id)
        ->and($ids)->not->toContain((int) $otherProgram->student_id);
});

it('filters students by gender', function (): void {
    $maleProgram = createVerifiedStudentApplication('STU-MALE-'.strtoupper(Str::random(4)));
    $femaleProgram = createVerifiedStudentApplication('STU-FEM-'.strtoupper(Str::random(4)));

    $femaleGender = Gender::query()->firstOrCreate(['title' => 'Female']);
    $femaleProgram->student->update(['gender_id' => $femaleGender->id]);

    createStudentEnrolmentForProgram($maleProgram);
    createStudentEnrolmentForProgram($femaleProgram);

    $user = User::factory()->create(['tenant_id' => $maleProgram->tenant_id]);
    Sanctum::actingAs($user);

    $maleResponse = $this->getJson(route('v1.students.index').'?gender=male');
    $maleResponse->assertOk();
    $maleIds = collect($maleResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($maleIds)->toContain((int) $maleProgram->student_id)
        ->and($maleIds)->not->toContain((int) $femaleProgram->student_id);

    $femaleResponse = $this->getJson(route('v1.students.index').'?gender=female');
    $femaleResponse->assertOk();
    $femaleIds = collect($femaleResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($femaleIds)->toContain((int) $femaleProgram->student_id)
        ->and($femaleIds)->not->toContain((int) $maleProgram->student_id);

    $allResponse = $this->getJson(route('v1.students.index'));
    $allResponse->assertOk();
    $allIds = collect($allResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($allIds)->toContain((int) $maleProgram->student_id)
        ->and($allIds)->toContain((int) $femaleProgram->student_id);
});

it('returns no students when department user has no assigned departments', function (): void {
    $program = createVerifiedStudentApplication('STU-NODEPT-'.strtoupper(Str::random(4)));
    createStudentEnrolmentForProgram($program);

    $departmentUser = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $departmentUser->givePermissionTo('viewOnlyOwnDepartment:departments');

    Sanctum::actingAs($departmentUser);

    $response = $this->getJson(route('v1.students.index'));
    $response->assertOk();
    expect($response->json('data'))->toBe([]);
});

it('returns the same distinct student count in index meta and stats when a student has multiple enrolments', function (): void {
    $program = createVerifiedStudentApplication('STU-MULTI-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($program);
    createStudentEnrolmentForProgram($program);

    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Sanctum::actingAs($user);

    $indexResponse = $this->getJson(route('v1.students.index'));
    $indexResponse->assertOk();

    $statsResponse = $this->getJson(route('v1.students.stats'));
    $statsResponse->assertOk();

    expect($indexResponse->json('meta.total'))
        ->toBe($statsResponse->json('filtered.total'))
        ->toBe($statsResponse->json('global.total'))
        ->toBe(1);
});

it('filters students by apprentice type', function (): void {
    $directProgram = createVerifiedStudentApplication('STU-DIR-'.strtoupper(Str::random(4)));
    $apprenticeProgram = createVerifiedStudentApplication('STU-APP-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($directProgram);
    createStudentEnrolmentForProgram($apprenticeProgram);

    StudentApprentice::query()->create([
        'tenant_id' => $apprenticeProgram->tenant_id,
        'student_id' => $apprenticeProgram->student_id,
        'calendar_year' => 2026,
        'employer' => 'Test Employer',
        'apprentice_number' => 'APP-001',
    ]);

    $user = User::factory()->create(['tenant_id' => $directProgram->tenant_id]);
    Sanctum::actingAs($user);

    $apprenticeResponse = $this->getJson(route('v1.students.index').'?student_type=apprentice');
    $apprenticeResponse->assertOk();
    $apprenticeIds = collect($apprenticeResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($apprenticeIds)->toContain((int) $apprenticeProgram->student_id)
        ->and($apprenticeIds)->not->toContain((int) $directProgram->student_id)
        ->and($apprenticeResponse->json('meta.total'))->toBe(1);

    $directResponse = $this->getJson(route('v1.students.index').'?student_type=direct');
    $directResponse->assertOk();
    $directIds = collect($directResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();
    expect($directIds)->toContain((int) $directProgram->student_id)
        ->and($directIds)->not->toContain((int) $apprenticeProgram->student_id)
        ->and($directResponse->json('meta.total'))->toBe(1);
});

it('matches filtered stats total with index meta total when student type filter is applied', function (): void {
    $directProgram = createVerifiedStudentApplication('STU-TYPE-'.strtoupper(Str::random(4)));
    $apprenticeProgram = createVerifiedStudentApplication('STU-TYPE-A-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($directProgram);
    createStudentEnrolmentForProgram($apprenticeProgram);

    StudentApprentice::query()->create([
        'tenant_id' => $apprenticeProgram->tenant_id,
        'student_id' => $apprenticeProgram->student_id,
        'calendar_year' => 2026,
    ]);

    $user = User::factory()->create(['tenant_id' => $directProgram->tenant_id]);
    Sanctum::actingAs($user);

    $indexResponse = $this->getJson(route('v1.students.index').'?student_type=apprentice');
    $statsResponse = $this->getJson(route('v1.students.stats').'?student_type=apprentice');

    $indexResponse->assertOk();
    $statsResponse->assertOk();

    expect($indexResponse->json('meta.total'))->toBe($statsResponse->json('filtered.total'));
});

it('requires department, level, and mode to match on the same enrolment', function (): void {
    $matchedProgram = createVerifiedStudentApplication('STU-SAME-'.strtoupper(Str::random(4)));
    createStudentEnrolmentForProgram($matchedProgram);

    $splitProgram = createVerifiedStudentApplication('STU-SPLIT-'.strtoupper(Str::random(4)));
    $ojet = \App\Models\Institution\ModeOfStudy::query()->firstOrCreate(['name' => 'Ojet']);
    $fullTime = \App\Models\Institution\ModeOfStudy::query()->firstOrCreate(['name' => 'Full Time']);

    $splitProgram->update(['mode_of_study_id' => $fullTime->id]);
    createStudentEnrolmentForProgram($splitProgram);

    $secondLevel = \App\Models\Institution\Level::factory()->create([
        'name' => 'ND-'.strtoupper(Str::random(4)),
        'calendar_type' => 'semester',
    ]);
    $secondDepartmentLevel = \App\Models\Institution\DepartmentLevel::query()->create([
        'tenant_id' => $splitProgram->tenant_id,
        'institution_department_id' => $splitProgram->institution_department_id,
        'level_id' => $secondLevel->id,
    ]);

    $secondApplication = \App\Models\Students\StudentApplication::query()->create([
        'tenant_id' => $splitProgram->tenant_id,
        'student_id' => $splitProgram->student_id,
        'institution_department_id' => $splitProgram->institution_department_id,
        'department_level_id' => $secondDepartmentLevel->id,
        'department_course_id' => $splitProgram->department_course_id,
        'intake_period_id' => $splitProgram->intake_period_id,
        'mode_of_study_id' => $ojet->id,
        'application_tracking_number' => 'APP-'.strtoupper(Str::random(8)),
        'program_status_id' => $splitProgram->program_status_id,
    ]);
    createStudentEnrolmentForProgram($secondApplication);

    $matchedProgram->update(['mode_of_study_id' => $ojet->id]);
    StudentEnrolment::query()
        ->where('student_id', $matchedProgram->student_id)
        ->update(['mode_of_study_id' => $ojet->id]);

    $user = User::factory()->create(['tenant_id' => $matchedProgram->tenant_id]);
    Sanctum::actingAs($user);

    $query = http_build_query([
        'department' => [$matchedProgram->institution_department_id],
        'level' => [$matchedProgram->departmentLevel->level_id],
        'mode_of_study' => [$ojet->id],
    ]);

    $indexResponse = $this->getJson(route('v1.students.index').'?'.$query);
    $statsResponse = $this->getJson(route('v1.students.stats').'?'.$query);

    $indexResponse->assertOk();
    $statsResponse->assertOk();

    $ids = collect($indexResponse->json('data'))->pluck('id')->map(static fn ($id) => (int) $id)->all();

    expect($ids)->toContain((int) $matchedProgram->student_id)
        ->and($ids)->not->toContain((int) $splitProgram->student_id)
        ->and($indexResponse->json('meta.total'))->toBe($statsResponse->json('filtered.total'))
        ->and($indexResponse->json('meta.total'))->toBe(1);
});
