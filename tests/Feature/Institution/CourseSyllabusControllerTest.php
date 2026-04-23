<?php

use App\Http\Requests\Institution\CourseSyllabusRequest;
use App\Models\Institution\Course;
use App\Models\Institution\CourseSyllabus;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

function makeCourseSyllabusContext(): array
{
    $tenant = Tenant::query()->firstOrFail();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $user->givePermissionTo([
        'create:course-syllabuses',
        'update:course-syllabuses',
        'delete:course-syllabuses',
        'viewAny:course-syllabuses',
    ]);

    $department = Department::factory()->create();
    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'csy_test_'.uniqid(),
        'description' => 'Course syllabus test department',
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

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    return compact('tenant', 'user', 'institutionDepartment', 'departmentLevelCourse');
}

it('validates required course syllabus fields', function () {
    $request = new CourseSyllabusRequest;
    $validator = Validator::make([], $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('institution_department_id'))->toBeTrue();
    expect($validator->errors()->has('department_level_course_id'))->toBeTrue();
    expect($validator->errors()->has('title'))->toBeTrue();
    expect($validator->errors()->has('code'))->toBeTrue();
    expect($validator->errors()->has('implementation_year'))->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

it('redirects guests from course syllabus show route', function () {
    $response = $this->get('/institution/departments/1/course-syllabuses/1/show');

    $response->assertRedirect('/login');
});

it('stores course syllabus with document and sets syllabus_document_id to media id', function () {
    $ctx = makeCourseSyllabusContext();

    $title = 'Syllabus Title '.uniqid();
    $code = 'SYL-'.uniqid();

    $response = $this->actingAs($ctx['user'])->post(route('department-course-syllabuses.store'), [
        'institution_department_id' => $ctx['institutionDepartment']->id,
        'department_level_course_id' => $ctx['departmentLevelCourse']->id,
        'title' => $title,
        'code' => $code,
        'implementation_year' => '2026',
        'status' => 'active',
        'syllabus_document' => UploadedFile::fake()->create('outline.pdf', 120, 'application/pdf'),
    ]);

    $response->assertSuccessful();

    $courseSyllabus = CourseSyllabus::query()->where('code', $code)->firstOrFail();
    expect($courseSyllabus->status->value)->toBe('active')
        ->and($courseSyllabus->syllabus_document_id)->not->toBeNull();

    $media = Media::query()->findOrFail($courseSyllabus->syllabus_document_id);
    expect($media->model_id)->toBe($courseSyllabus->id)
        ->and($media->collection_name)->toBe(CourseSyllabus::MEDIA_COLLECTION_SYLLABUS_DOCUMENT);
});

it('updates course syllabus document replacing media id', function () {
    $ctx = makeCourseSyllabusContext();

    $courseSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => $ctx['tenant']->id,
        'institution_department_id' => $ctx['institutionDepartment']->id,
        'department_level_course_id' => $ctx['departmentLevelCourse']->id,
        'title' => 'Existing '.uniqid(),
        'code' => 'EX-'.uniqid(),
        'implementation_year' => '2025',
        'status' => 'active',
    ]);

    $this->actingAs($ctx['user'])->put(route('department-course-syllabuses.update', $courseSyllabus), [
        'institution_department_id' => $ctx['institutionDepartment']->id,
        'department_level_course_id' => $ctx['departmentLevelCourse']->id,
        'title' => $courseSyllabus->title,
        'code' => $courseSyllabus->code,
        'implementation_year' => '2026',
        'status' => 'terminated',
        'syllabus_document' => UploadedFile::fake()->create('revised.pdf', 100, 'application/pdf'),
    ])->assertSuccessful();

    $courseSyllabus->refresh();
    expect($courseSyllabus->status->value)->toBe('terminated')
        ->and($courseSyllabus->syllabus_document_id)->not->toBeNull();

    $media = Media::query()->findOrFail($courseSyllabus->syllabus_document_id);
    expect($media->file_name)->toContain('revised');
});
