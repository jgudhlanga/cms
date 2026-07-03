<?php

use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\AcademicCalendarStudentEnrolment;
use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\StudentEnrolment;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\DB;

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';
require_once __DIR__.'/../../Support/SyllabusModuleTestHelpers.php';

function buildClassStaffingContext(): array
{
    $context = buildDepartmentClassContext();
    ensureSyllabusModuleRoles();

    $context['user']->givePermissionTo([
        'create:course-syllabuses',
        'update:course-syllabuses',
        'create:course-syllabus-modules',
        'update:course-syllabus-modules',
        'viewAny:course-syllabus-modules',
        'view:course-syllabus-modules',
        'view:course-syllabuses',
    ]);

    $departmentLevelCourse = \App\Models\Institution\DepartmentLevelCourse::query()
        ->where('department_course_id', $context['departmentCourse']->id)
        ->where('department_level_id', $context['departmentLevel']->id)
        ->firstOrFail();

    $context['courseSyllabus'] = CourseSyllabus::query()->create([
        'tenant_id' => $context['tenant']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_course_id' => $departmentLevelCourse->id,
        'title' => 'Staffing Syllabus '.uniqid(),
        'code' => 'STF-SYL-'.uniqid(),
        'implementation_year' => '2026',
        'status' => 'active',
    ]);

    $context['semesterOne'] = \App\Models\AcademicCalendars\AcademicYearOption::query()->firstOrCreate(
        ['slug' => 'semester-1'],
        ['name' => 'Semester 1', 'description' => null],
    );

    $semesterConfig = ClassConfig::query()->create([
        'calendar_year' => $context['calendar']->calendar_year,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'academic_year_option_id' => $context['semesterOne']->id,
        'course_syllabus_ids' => [$context['courseSyllabus']->id],
        'students_per_class' => 2,
    ]);

    $module = CourseSyllabusModule::query()->create([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_id' => $context['courseSyllabus']->id,
        'academic_year_option_id' => $context['semesterOne']->id,
        'title' => 'Staffing Module',
        'code' => 'STF-'.uniqid(),
        'shared' => false,
    ]);

    $academicCalendarClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenant']->id,
        'class_config_id' => $context['classConfig']->id,
        'name' => 'LEVEL-1-FULL-TIME-1',
    ]);

    $studentApplication = createFinalStudentApplication($context, 'staffing-student@example.com');
    $studentEnrolmentId = StudentEnrolment::query()
        ->where('student_application_id', $studentApplication->id)
        ->value('id');

    AcademicCalendarStudentEnrolment::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $academicCalendarClass->id,
        'student_enrolment_id' => $studentEnrolmentId,
    ]);

    return array_merge($context, compact('semesterConfig', 'module', 'academicCalendarClass'));
}

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
});

test('assign tutor creates class metadata for lecturer type', function () {
    $context = buildClassStaffingContext();
    $tutor = makeSyllabusModuleLecturerStaff($context);

    $this->actingAs($context['user'])->patch(
        route('academic-calendars.department-classes.assign-tutor', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        ['staff_id' => $tutor->id],
    )->assertRedirect();

    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->value('staff_id'))->toBe($tutor->id);
});

test('assign tutor can be cleared', function () {
    $context = buildClassStaffingContext();
    $tutor = makeSyllabusModuleLecturerStaff($context);
    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    AcademicCalendarClassMetaData::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'staff_id' => $tutor->id,
        'class_metadata_type_id' => $lecturerTypeId,
    ]);

    $this->actingAs($context['user'])->patch(
        route('academic-calendars.department-classes.assign-tutor', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        ['staff_id' => null],
    )->assertRedirect();

    expect(AcademicCalendarClassMetaData::query()
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('class_metadata_type_id', $lecturerTypeId)
        ->exists())->toBeFalse();
});

test('sync class module lecturers stores class scoped pivot rows', function () {
    $context = buildClassStaffingContext();
    $firstLecturer = makeSyllabusModuleLecturerStaff($context);
    $secondLecturer = makeSyllabusModuleLecturerStaff($context);

    $this->actingAs($context['user'])->put(
        route('academic-calendars.department-classes.sync-module-lecturers', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        [
            'academic_year_option_id' => $context['semesterOne']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_ids' => [$firstLecturer->id, $secondLecturer->id],
        ],
    )->assertRedirect();

    $rows = DB::table('course_syllabus_module_lecturers')
        ->where('course_syllabus_module_id', $context['module']->id)
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->pluck('staff_id')
        ->map(fn ($id) => (int) $id)
        ->sort()
        ->values()
        ->all();

    expect($rows)->toBe(collect([$firstLecturer->id, $secondLecturer->id])->sort()->values()->all());
});

test('copy defaults copies template lecturers to class scoped rows', function () {
    $context = buildClassStaffingContext();
    $lecturer = makeSyllabusModuleLecturerStaff($context);

    DB::table('course_syllabus_module_lecturers')->insert([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'staff_id' => $lecturer->id,
        'academic_calendar_class_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($context['user'])->post(
        route('academic-calendars.department-classes.copy-module-lecturer-defaults', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        ['academic_year_option_id' => $context['semesterOne']->id],
    )->assertRedirect();

    expect(DB::table('course_syllabus_module_lecturers')
        ->where('course_syllabus_module_id', $context['module']->id)
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('staff_id', $lecturer->id)
        ->exists())->toBeTrue();
});

test('syllabus module template sync does not remove class scoped lecturer rows', function () {
    $context = buildClassStaffingContext();
    $templateLecturer = makeSyllabusModuleLecturerStaff($context);
    $classLecturer = makeSyllabusModuleLecturerStaff($context);

    DB::table('course_syllabus_module_lecturers')->insert([
        [
            'tenant_id' => $context['tenant']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_id' => $templateLecturer->id,
            'academic_calendar_class_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'tenant_id' => $context['tenant']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_id' => $classLecturer->id,
            'academic_calendar_class_id' => $context['academicCalendarClass']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $this->actingAs($context['user'])->put(route('course-syllabus-modules.update', $context['module']), [
        'course_syllabus_id' => $context['courseSyllabus']->id,
        'academic_year_option_id' => $context['semesterOne']->id,
        'title' => $context['module']->title,
        'code' => $context['module']->code,
        'prerequisite_module_ids' => [],
        'shared' => false,
        'staff_ids' => [],
    ])->assertOk();

    expect(DB::table('course_syllabus_module_lecturers')
        ->where('course_syllabus_module_id', $context['module']->id)
        ->where('academic_calendar_class_id', $context['academicCalendarClass']->id)
        ->where('staff_id', $classLecturer->id)
        ->exists())->toBeTrue();
});

test('sync class module lecturers returns json when accept header is application json', function () {
    $context = buildClassStaffingContext();
    $firstLecturer = makeSyllabusModuleLecturerStaff($context);
    $secondLecturer = makeSyllabusModuleLecturerStaff($context);

    $response = $this->actingAs($context['user'])->putJson(
        route('academic-calendars.department-classes.sync-module-lecturers', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        [
            'academic_year_option_id' => $context['semesterOne']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_ids' => [$firstLecturer->id, $secondLecturer->id],
        ],
    );

    $response->assertOk()
        ->assertJsonPath('moduleId', $context['module']->id)
        ->assertJsonPath('staffIds', collect([$firstLecturer->id, $secondLecturer->id])->sort()->values()->all());

    expect($response->json('message'))->toBeString()->not->toBeEmpty();
});

test('copy defaults returns json with semester modules when accept header is application json', function () {
    $context = buildClassStaffingContext();
    $lecturer = makeSyllabusModuleLecturerStaff($context);

    DB::table('course_syllabus_module_lecturers')->insert([
        'tenant_id' => $context['tenant']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'staff_id' => $lecturer->id,
        'academic_calendar_class_id' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($context['user'])->postJson(
        route('academic-calendars.department-classes.copy-module-lecturer-defaults', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['calendar']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
        ]),
        ['academic_year_option_id' => $context['semesterOne']->id],
    );

    $response->assertOk()
        ->assertJsonPath('semesterModules.0.moduleId', $context['module']->id)
        ->assertJsonPath('semesterModules.0.staffIds', [$lecturer->id]);

    expect($response->json('message'))->toBeString()->not->toBeEmpty();
});

test('department classes page includes tutor and staffing summary when semester is selected', function () {
    $context = buildClassStaffingContext();
    $tutor = makeSyllabusModuleLecturerStaff($context);
    $lecturerTypeId = ClassMetaDataType::query()
        ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
        ->value('id');

    AcademicCalendarClassMetaData::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'staff_id' => $tutor->id,
        'class_metadata_type_id' => $lecturerTypeId,
    ]);

    $response = $this->actingAs($context['user'])->get(route('academic-calendars.department-classes', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'class_config_id' => $context['classConfig']->id,
        'academic_year_option_id' => $context['semesterOne']->id,
    ]));

    $response->assertSuccessful();
    $page = $response->viewData('page');

    $previewClass = collect(data_get($page, 'props.previewClasses'))
        ->first(fn (array $preview): bool => (int) ($preview['academicCalendarClassId'] ?? 0) === (int) $context['academicCalendarClass']->id);

    expect(data_get($page, 'props.staffingSummary.tutorsAssigned'))->toBe(1)
        ->and(data_get($previewClass, 'tutor.id'))->toBe($tutor->id)
        ->and(data_get($page, 'props.selectedAcademicYearOptionId'))->toBe($context['semesterOne']->id);
});
