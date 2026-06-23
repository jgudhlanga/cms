<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../Api/V1/Students/StudentIndexFilterTest.php';

it('requires export permission to download student list', function (): void {
    $program = createVerifiedStudentProgram('STU-EXP-'.strtoupper(Str::random(4)));
    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    $user->givePermissionTo('viewAny:students');

    createStudentEnrolmentForProgram($program);

    $this->actingAs($user)
        ->get(route('students.export', ['department' => [$program->institution_department_id]]))
        ->assertForbidden();
});

it('validates department is required for student list export', function (): void {
    $program = createVerifiedStudentProgram('STU-EXP-'.strtoupper(Str::random(4)));
    $user = User::factory()->create(['tenant_id' => $program->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $this->actingAs($user)
        ->from(route('students.index'))
        ->get(route('students.export'))
        ->assertRedirect(route('students.index'))
        ->assertSessionHasErrors(['department']);
});

it('exports students filtered by department', function (): void {
    $matchedProgram = createVerifiedStudentProgram('STU-MAT-'.strtoupper(Str::random(4)));
    $otherProgram = createVerifiedStudentProgram('STU-OTH-'.strtoupper(Str::random(4)));

    createStudentEnrolmentForProgram($matchedProgram);
    createStudentEnrolmentForProgram($otherProgram);

    $user = User::factory()->create(['tenant_id' => $matchedProgram->tenant_id]);
    Permission::findOrCreate('export:students', 'web');
    $user->givePermissionTo(['viewAny:students', 'export:students']);

    $response = $this->actingAs($user)->get(route('students.export', [
        'department' => [$matchedProgram->institution_department_id],
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($response->getFile()->getPathname());
    $rows = $spreadsheet->getActiveSheet()->toArray();
    $flat = collect($rows)->flatten()->filter()->implode(' ');

    expect($flat)->toContain('STU-MAT-');
    expect($flat)->not->toContain('STU-OTH-');
});
