<?php

use App\Enums\Shared\ClassListTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Enrolments\ClassList;
use App\Models\Rbac\Permission;
use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;

function makeDepartmentClassListSummariesUser(array $permissions): User
{
    $user = User::factory()->create(['tenant_id' => TenantEnum::HARARE_POLY->id()]);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    return $user;
}

it('returns json api class list summaries filtered by type with mode totals', function () {
    $provisional = createVerifiedStudentApplication('CL-SUM-PROV-01');
    ClassList::query()->where('student_application_id', $provisional->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $verified = createVerifiedStudentApplication('CL-SUM-VER-01');
    $verified->update([
        'institution_department_id' => $provisional->institution_department_id,
        'department_level_id' => $provisional->department_level_id,
        'department_course_id' => $provisional->department_course_id,
        'intake_period_id' => $provisional->intake_period_id,
        'mode_of_study_id' => $provisional->mode_of_study_id,
        'tenant_id' => $provisional->tenant_id,
    ]);
    ClassList::query()->where('student_application_id', $verified->id)->update([
        'type' => ClassListTypeEnum::VERIFIED->value,
    ]);

    $user = makeDepartmentClassListSummariesUser(['verify:class-lists']);
    Sanctum::actingAs($user);

    $totals = $this->getJson(route('v1.department-metadata.class-lists', [
        'institution_department' => $provisional->institution_department_id,
        'intake_period_id' => $provisional->intake_period_id,
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]));

    $totals->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonStructure(['meta' => ['modeTotals']]);

    expect(collect($totals->json('meta.modeTotals'))->firstWhere('modeOfStudyId', $provisional->mode_of_study_id)['count'] ?? 0)
        ->toBe(1);

    $rows = $this->getJson(route('v1.department-metadata.class-lists', [
        'institution_department' => $provisional->institution_department_id,
        'intake_period_id' => $provisional->intake_period_id,
        'mode_of_study_id' => $provisional->mode_of_study_id,
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]));

    $rows->assertOk()
        ->assertJsonPath('data.0.type', 'department-enrolment-summaries')
        ->assertJsonPath('data.0.attributes.enrolmentsCount', 1)
        ->assertJsonPath('data.0.attributes.departmentCourseId', $provisional->department_course_id);
});

it('forbids class list summaries without the mapped ability', function () {
    $application = createVerifiedStudentApplication('CL-SUM-FORBID-01');
    ClassList::query()->where('student_application_id', $application->id)->update([
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]);

    $user = makeDepartmentClassListSummariesUser(['view:student-applications']);
    Sanctum::actingAs($user);

    $this->getJson(route('v1.department-metadata.class-lists', [
        'institution_department' => $application->institution_department_id,
        'intake_period_id' => $application->intake_period_id,
        'type' => ClassListTypeEnum::PROVISIONAL->value,
    ]))->assertForbidden();
});
