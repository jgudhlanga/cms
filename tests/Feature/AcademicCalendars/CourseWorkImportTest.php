<?php

use App\Exports\AcademicCalendars\CourseWorkImportTemplateExport;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkImportLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/../JsonApi/V1/AcademicCalendars/CourseWorkMarkTest.php';

/**
 * @param  array<string, mixed>  $context
 * @return array<string, mixed>
 */
function courseWorkImportRouteParams(array $context): array
{
    $classConfig = $context['academicCalendarClass']->classConfig;

    return [
        'institution_department' => $classConfig->institution_department_id,
        'calendar_year' => $classConfig->calendar_year,
        'class_config_id' => $classConfig->id,
        'department_course_id' => $classConfig->department_course_id,
        'department_level_id' => $classConfig->department_level_id,
        'mode_of_study_id' => $classConfig->mode_of_study_id,
    ];
}

/**
 * @param  array<string, mixed>  $context
 */
function courseWorkImportPreviewAndProcess(UploadedFile $file, array $context, int $moduleId): void
{
    test()->actingAs($context['user']);

    $previewResponse = test()->post(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
        'module' => $moduleId,
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful();

    $previewToken = $previewResponse->json('previewToken');
    expect($previewToken)->not->toBeEmpty();

    test()->post(route('academic-calendars.department-classes.course-work-import.process', courseWorkImportRouteParams($context)), [
        'module' => $moduleId,
        'preview_token' => $previewToken,
    ])->assertRedirect();
}

test('course work import page requires import permission', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('viewAny:course-work', 'web');
    $context['user']->givePermissionTo('viewAny:course-work');

    $classConfig = $context['academicCalendarClass']->classConfig;

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-import', [
            'institution_department' => $classConfig->institution_department_id,
            'calendar_year' => $classConfig->calendar_year,
            'class_config_id' => $classConfig->id,
            'department_course_id' => $classConfig->department_course_id,
            'department_level_id' => $classConfig->department_level_id,
            'mode_of_study_id' => $classConfig->mode_of_study_id,
        ]))
        ->assertForbidden();
});

test('authorized user can view course work import page', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('import:course-work', 'web');
    $context['user']->givePermissionTo('import:course-work');

    $classConfig = $context['academicCalendarClass']->classConfig;

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-import', [
            'institution_department' => $classConfig->institution_department_id,
            'calendar_year' => $classConfig->calendar_year,
            'class_config_id' => $classConfig->id,
            'department_course_id' => $classConfig->department_course_id,
            'department_level_id' => $classConfig->department_level_id,
            'mode_of_study_id' => $classConfig->mode_of_study_id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/academicCalendars/DepartmentAcademicCalendarClassConfigCourseWorkImport')
            ->has('classConfig'));
});

test('course work import template requires import permission', function () {
    $context = createCourseWorkJsonApiContext();

    $classConfig = $context['academicCalendarClass']->classConfig;

    $this->actingAs($context['user'])
        ->get(route('academic-calendars.department-classes.course-work-import.template', [
            'institution_department' => $classConfig->institution_department_id,
            'calendar_year' => $classConfig->calendar_year,
            'class_config_id' => $classConfig->id,
            'module' => $context['module']->id,
        ]))
        ->assertForbidden();
});

test('authorized user can download course work import template', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('import:course-work', 'web');
    $context['user']->givePermissionTo('import:course-work');

    $classConfig = $context['academicCalendarClass']->classConfig;

    $response = $this->actingAs($context['user'])->get(route('academic-calendars.department-classes.course-work-import.template', [
        'institution_department' => $classConfig->institution_department_id,
        'calendar_year' => $classConfig->calendar_year,
        'class_config_id' => $classConfig->id,
        'department_course_id' => $classConfig->department_course_id,
        'department_level_id' => $classConfig->department_level_id,
        'mode_of_study_id' => $classConfig->mode_of_study_id,
        'module' => $context['module']->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

test('course work import template service builds one row per student assessment', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    /** @var CourseWorkImportTemplateService $service */
    $service = app(CourseWorkImportTemplateService::class);
    $data = $service->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    expect($data['rows'])->toHaveCount(1)
        ->and($data['rows'][0]['studentEnrolmentId'])->toBe($context['studentEnrolment']->id)
        ->and($data['rows'][0]['moduleId'])->toBe($context['module']->id)
        ->and($data['rows'][0]['assessmentTypeId'])->toBe($context['assessmentType']->id)
        ->and($data['rows'][0]['mark'])->toBeNull()
        ->and($data['rows'][0]['remark'])->toBeNull();
});

test('course work import creates marks and batch audit log', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 82;
    $data['rows'][0]['remark'] = 'Imported remark';

    $relativePath = 'test-course-work-import-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $absolutePath = storage_path('app/'.$relativePath);

    $file = new UploadedFile($absolutePath, 'course-work-import.xlsx', null, null, true);

    $classConfig = $context['academicCalendarClass']->classConfig;

    courseWorkImportPreviewAndProcess($file, $context, (int) $context['module']->id);

    expect(CourseWorkMark::query()->where([
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
    ])->value('mark'))->toBe(82);

    expect(CourseWorkImportLog::query()->count())->toBe(1);
    expect(CourseWorkAuditLog::query()->count())->toBe(1);
});

test('course work import preview rejects file with no marks', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $relativePath = 'test-course-work-import-empty-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    $this->actingAs($context['user'])
        ->post(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
            'module' => $context['module']->id,
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');

    expect(CourseWorkMark::query()->count())->toBe(0);
});

test('course work import preview rejects invalid marks', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 85.5;

    $relativePath = 'test-course-work-import-invalid-mark-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    $this->actingAs($context['user'])
        ->postJson(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
            'module' => $context['module']->id,
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.failed', 1)
        ->assertJsonPath('summary.succeeded', 0)
        ->assertJsonPath('rows.0.action', 'fail');
});

test('course work import preview rejects rows with remark but no mark', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = null;
    $data['rows'][0]['remark'] = 'Remark only';

    $relativePath = 'test-course-work-import-remark-only-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    $response = $this->actingAs($context['user'])
        ->postJson(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
            'module' => $context['module']->id,
            'file' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.failed', 1)
        ->assertJsonPath('summary.succeeded', 0)
        ->assertJsonPath('rows.0.action', 'fail');
});

test('course work import updates existing mark instead of creating duplicate', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('update:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'update:course-work']);

    CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 60,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 91;

    $relativePath = 'test-course-work-import-update-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    courseWorkImportPreviewAndProcess($file, $context, (int) $context['module']->id);

    expect(CourseWorkMark::query()->count())->toBe(1)
        ->and(CourseWorkMark::query()->value('mark'))->toBe(91);
});

test('course work import skips duplicate rows within the same file', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 70;
    $data['rows'][] = array_merge($data['rows'][0], ['mark' => 99]);

    $relativePath = 'test-course-work-import-dup-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    courseWorkImportPreviewAndProcess($file, $context, (int) $context['module']->id);

    expect(CourseWorkMark::query()->count())->toBe(1)
        ->and(CourseWorkMark::query()->value('mark'))->toBe(70);

    $result = session('courseWorkImportResult');
    expect($result['rowsSucceeded'])->toBe(1)
        ->and($result['rowsSkipped'])->toBe(1);
});

test('course work import restores soft deleted mark instead of creating duplicate', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    $deletedMark = CourseWorkMark::query()->create([
        'tenant_id' => $context['tenant']->id,
        'student_enrolment_id' => $context['studentEnrolment']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'mark' => 55,
        'created_by' => $context['user']->id,
        'updated_by' => $context['user']->id,
    ]);
    $deletedMark->delete();

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 88;

    $relativePath = 'test-course-work-import-restore-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    courseWorkImportPreviewAndProcess($file, $context, (int) $context['module']->id);

    expect(CourseWorkMark::query()->count())->toBe(1)
        ->and(CourseWorkMark::withTrashed()->count())->toBe(1)
        ->and(CourseWorkMark::query()->value('mark'))->toBe(88);
});

test('course work import preview returns row summary without persisting marks', function () {
    $context = createCourseWorkJsonApiContext();
    $context['assessmentType']->update(['weight_percent' => 20]);

    Permission::findOrCreate('import:course-work', 'web');
    Permission::findOrCreate('create:course-work', 'web');
    $context['user']->givePermissionTo(['import:course-work', 'create:course-work']);

    /** @var CourseWorkImportTemplateService $templateService */
    $templateService = app(CourseWorkImportTemplateService::class);
    $data = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );

    $data['rows'][0]['mark'] = 77;

    $relativePath = 'test-course-work-import-preview-'.uniqid().'.xlsx';
    Excel::store(new CourseWorkImportTemplateExport($data), $relativePath, 'local');
    $file = new UploadedFile(storage_path('app/'.$relativePath), 'course-work-import.xlsx', null, null, true);

    $response = $this->actingAs($context['user'])
        ->post(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
            'module' => $context['module']->id,
            'file' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.creates', 1)
        ->assertJsonPath('rows.0.action', 'create')
        ->assertJsonPath('rows.0.mark', 77);

    expect(CourseWorkMark::query()->count())->toBe(0);
});

test('course work import preview rejects unsupported file types', function () {
    $context = createCourseWorkJsonApiContext();
    Permission::findOrCreate('import:course-work', 'web');
    $context['user']->givePermissionTo('import:course-work');

    $file = UploadedFile::fake()->create('marks.pdf', 100, 'application/pdf');

    $this->actingAs($context['user'])
        ->post(route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)), [
            'module' => $context['module']->id,
            'file' => $file,
        ])
        ->assertSessionHasErrors('file');
});
