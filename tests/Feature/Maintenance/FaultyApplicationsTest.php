<?php

use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;

require_once __DIR__.'/MaintenanceControllerTest.php';

it('redirects guests from the faulty applications page', function (): void {
    $this->get(route('maintenance.faulty-applications'))
        ->assertRedirect('/login');
});

it('forbids unauthorised users from the faulty applications data endpoint', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('maintenance.faulty-applications.data'))
        ->assertForbidden();
});

it('renders the faulty applications page', function (): void {
    actingAsRootMaintenanceUser();

    $this->get(route('maintenance.faulty-applications'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('maintenance/FaultyApplications'));
});

it('excludes complete applications', function (): void {
    actingAsRootMaintenanceUser();
    createVerifiedStudentApplication('STU-COMPLETE-1');

    $this->get(route('maintenance.faulty-applications.data'))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});

/**
 * The programme foreign keys on `student_applications` are NOT NULL, so real
 * gaps show up either as a nullable column or as a dangling (soft deleted)
 * related record.
 */
function breakApplicationProgramme(StudentApplication $application, string $reason): void
{
    match ($reason) {
        'missing_level' => Level::query()
            ->whereKey($application->departmentLevel->level_id)
            ->update(['name' => ' ']),
        'missing_department' => InstitutionDepartment::query()
            ->whereKey($application->institution_department_id)
            ->delete(),
        'missing_course' => DepartmentCourse::query()
            ->whereKey($application->department_course_id)
            ->delete(),
        default => StudentApplication::query()
            ->whereKey($application->id)
            ->update([$reason === 'missing_intake' ? 'intake_period_id' : 'mode_of_study_id' => null]),
    };
}

it('flags each programme gap with a reason', function (string $reason): void {
    actingAsRootMaintenanceUser();
    $application = createVerifiedStudentApplication('STU-'.strtoupper(str()->random(5)));

    breakApplicationProgramme($application, $reason);

    $response = $this->get(route('maintenance.faulty-applications.data'))->assertSuccessful();

    expect($response->json('data.0.id'))->toBe($application->id)
        ->and($response->json('data.0.attributes.reasons'))->toContain($reason);
})->with([
    'missing_level',
    'missing_department',
    'missing_course',
    'missing_mode_of_study',
    'missing_intake',
]);

it('searches faulty applications by tracking number', function (): void {
    actingAsRootMaintenanceUser();
    $application = createVerifiedStudentApplication('STU-FAULTY-SEARCH');
    breakApplicationProgramme($application, 'missing_level');

    $other = createVerifiedStudentApplication('STU-FAULTY-OTHER');
    breakApplicationProgramme($other, 'missing_intake');

    $response = $this->get(route('maintenance.faulty-applications.data', [
        'search' => $application->application_tracking_number,
    ]))->assertSuccessful();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.id'))->toBe($application->id);
});

it('counts faulty applications on the maintenance hub', function (): void {
    actingAsRootMaintenanceUser();
    $application = createVerifiedStudentApplication('STU-FAULTY-COUNT');
    StudentApplication::query()->whereKey($application->id)->update(['mode_of_study_id' => null]);

    $this->get(route('maintenance.exports.counts'))
        ->assertSuccessful()
        ->assertJsonPath('faultyApplications', 1);
});

it('lets a data maintenance user open the programme editor for a faulty application', function (): void {
    $application = createVerifiedStudentApplication('STU-FAULTY-EDIT');
    breakApplicationProgramme($application, 'missing_mode_of_study');
    actingAsDataMaintenanceUser($application->tenant_id);

    $this->get(route('students.program-edit', [
        'student_application' => $application,
        'from' => 'maintenance',
        'return' => route('maintenance.faulty-applications', absolute: false),
    ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('students/EditStudentApplication'));
});

it('lets a data maintenance user restore a missing mode of study', function (): void {
    $application = createVerifiedStudentApplication('STU-FAULTY-FIX');
    $modeOfStudyId = $application->mode_of_study_id;
    ensureProgrammeOffering(
        (int) $application->department_course_id,
        (int) $application->department_level_id,
        (int) $modeOfStudyId,
    );
    breakApplicationProgramme($application, 'missing_mode_of_study');
    actingAsDataMaintenanceUser($application->tenant_id);

    $this->put(route('students.program-update', $application), [
        'institution_department_id' => $application->institution_department_id,
        'department_level_id' => $application->department_level_id,
        'department_course_id' => $application->department_course_id,
        'mode_of_study_id' => $modeOfStudyId,
    ])->assertSuccessful();

    $this->get(route('maintenance.faulty-applications.data'))
        ->assertSuccessful()
        ->assertJsonCount(0, 'data');
});
