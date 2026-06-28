<?php

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';

function seedClassMetaDataTypes(): void
{
    foreach (ClassMetaDataTypeEnum::cases() as $row) {
        ClassMetaDataType::query()->firstOrCreate(
            ['name' => $row->value],
            ['description' => $row->label()],
        );
    }
}

function createDepartmentStaffForClassTests(array $context): Staff
{
    $title = Title::query()->firstOrCreate(['name' => 'Mr Class Test']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Male Class Test']);
    $marital = MaritalStatus::query()->firstOrCreate(['title' => 'Single Class Test']);
    $employmentType = EmploymentType::query()->firstOrCreate(
        ['name' => 'full-time-class-test'],
        ['description' => 'Full time'],
    );

    $staffUser = User::factory()->create([
        'tenant_id' => $context['tenant']->id,
        'first_name' => 'Class',
        'last_name' => 'Lecturer',
    ]);

    $staff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $staffUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'CLASS-LECT-'.random_int(1000, 9999),
    ]);

    $context['institutionDepartment']->staff()->syncWithoutDetaching([$staff->id]);

    return $staff;
}

function createPopulatedClassContext(): array
{
    $context = buildDepartmentClassContext();
    createFinalStudentApplication($context, 'class-export-one@example.com');
    createFinalStudentApplication($context, 'class-export-two@example.com');

    $context['user']->givePermissionTo([
        'export:academic-calendars',
        'update:academic-calendars',
    ]);

    test()->actingAs($context['user'])->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $context['academicCalendarClass'] = AcademicCalendarClass::query()
        ->where('class_config_id', $context['classConfig']->id)
        ->orderBy('id')
        ->firstOrFail();

    return $context;
}

test('class list export requires export permission', function () {
    $context = createPopulatedClassContext();
    $context['user']->revokePermissionTo('export:academic-calendars');

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [$context['academicCalendarClass']->id],
        ]))
        ->assertForbidden();
});

test('class list export rejects empty class selection', function () {
    $context = createPopulatedClassContext();

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [],
        ]))
        ->assertSessionHasErrors('class_ids');
});

test('authorized user can export class list pdf for selected classes', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');
    $context = createPopulatedClassContext();

    $response = $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'class_config_id' => $context['classConfig']->id,
            'class_ids' => [$context['academicCalendarClass']->id],
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('authorized user can export single class list pdf from show route', function () {
    Permission::findOrCreate('export:academic-calendars', 'web');
    $context = createPopulatedClassContext();

    $response = $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.class-list.export-class', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('assign class lecturer requires update permission', function () {
    seedClassMetaDataTypes();
    $context = createPopulatedClassContext();
    $staff = createDepartmentStaffForClassTests($context);
    $context['user']->revokePermissionTo('update:academic-calendars');

    $this->actingAs($context['user'])
        ->patch(route('academic-calendars.department-classes.assign-lecturer', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]), [
            'staff_id' => $staff->id,
        ])
        ->assertForbidden();
});

test('authorized user can assign update and clear class lecturer', function () {
    seedClassMetaDataTypes();
    $context = createPopulatedClassContext();
    $staff = createDepartmentStaffForClassTests($context);
    $otherStaff = createDepartmentStaffForClassTests($context);

    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    $this->actingAs($context['user'])
        ->patch(route('academic-calendars.department-classes.assign-lecturer', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]), [
            'staff_id' => $staff->id,
        ])
        ->assertSessionHas('success');

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->value('staff_id'))->toBe($staff->id);

    $this->actingAs($context['user'])
        ->patch(route('academic-calendars.department-classes.assign-lecturer', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]), [
            'staff_id' => $otherStaff->id,
        ])
        ->assertSessionHas('success');

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->count())->toBe(1);

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->value('staff_id'))->toBe($otherStaff->id);

    $this->actingAs($context['user'])
        ->patch(route('academic-calendars.department-classes.assign-lecturer', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]), [
            'staff_id' => null,
        ])
        ->assertSessionHas('success');

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->exists())->toBeFalse();
});

test('assign class lecturer rejects staff outside department', function () {
    seedClassMetaDataTypes();
    $context = createPopulatedClassContext();

    $title = Title::query()->firstOrCreate(['name' => 'Ms Outside']);
    $gender = Gender::query()->firstOrCreate(['title' => 'Female Outside']);
    $marital = MaritalStatus::query()->firstOrCreate(['title' => 'Single Outside']);
    $employmentType = EmploymentType::query()->firstOrCreate(
        ['name' => 'part-time-outside'],
        ['description' => 'Part time'],
    );

    $outsideUser = User::factory()->create(['tenant_id' => $context['tenant']->id]);
    $outsideStaff = Staff::query()->create([
        'tenant_id' => $context['tenant']->id,
        'user_id' => $outsideUser->id,
        'title_id' => $title->id,
        'gender_id' => $gender->id,
        'marital_status_id' => $marital->id,
        'employment_type_id' => $employmentType->id,
        'employee_number' => 'OUTSIDE-'.random_int(1000, 9999),
    ]);

    $this->actingAs($context['user'])
        ->patch(route('academic-calendars.department-classes.assign-lecturer', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]), [
            'staff_id' => $outsideStaff->id,
        ])
        ->assertSessionHasErrors('staff_id');
});
