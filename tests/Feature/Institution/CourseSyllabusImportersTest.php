<?php

use App\Importers\Institution\CourseSyllabusImporter;
use App\Importers\Institution\CourseSyllabusModuleImporter;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Tenants\Tenant;
use LaravelIngest\Enums\IngestStatus;
use LaravelIngest\Models\IngestRun;
use LaravelIngest\Services\RowProcessor;

function makeSyllabusImportContext(): array
{
    $tenant = Tenant::query()->findOrFail(1);

    $department = Department::factory()->create(['name' => 'Engineering']);
    $course = Course::factory()->create(['name' => 'Civil Technology']);
    $level = Level::factory()->create(['name' => 'Level 1']);

    $institutionDepartment = InstitutionDepartment::query()->create([
        'tenant_id' => $tenant->id,
        'department_id' => $department->id,
        'department_code' => 'ENG-TEST-'.uniqid(),
        'description' => 'Engineering test department',
    ]);

    $departmentCourse = DepartmentCourse::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'course_id' => $course->id,
    ]);

    $departmentLevel = DepartmentLevel::query()->create([
        'tenant_id' => $tenant->id,
        'institution_department_id' => $institutionDepartment->id,
        'level_id' => $level->id,
    ]);

    $departmentLevelCourse = DepartmentLevelCourse::query()->create([
        'department_course_id' => $departmentCourse->id,
        'department_level_id' => $departmentLevel->id,
    ]);

    return compact('tenant', 'institutionDepartment', 'departmentLevelCourse');
}

function runImporter(string $importerClass, array $rows): array
{
    $ingestRun = IngestRun::query()->create([
        'importer' => class_basename($importerClass),
        'status' => IngestStatus::PROCESSING,
    ]);

    $chunk = collect($rows)
        ->values()
        ->map(fn (array $row, int $index): array => [
            'number' => $index + 1,
            'data' => $row,
        ])
        ->all();

    /** @var RowProcessor $rowProcessor */
    $rowProcessor = app(RowProcessor::class);
    $results = $rowProcessor->processChunk($ingestRun, app($importerClass)->getConfig(), $chunk, false);
    $errors = $ingestRun->rows()->where('status', 'failed')->pluck('errors')->all();

    return [
        'run' => $ingestRun,
        'results' => $results,
        'errors' => $errors,
    ];
}

it('imports course syllabuses and modules from syllabus xlsx', function () {
    makeSyllabusImportContext();

    $rows = [
        [
            'DEPARTMENT' => 'Engineering',
            'LEVEL' => 'Level 1',
            'COURSE_TITLE' => 'Civil Technology',
            'COURSE_CODE' => 'CT-101',
            'IMPLEMENTATION_YEAR' => '2026',
            'MODULE_TITLE' => 'Module Intro',
            'MODULE_CODE' => 'MOD-CT-101',
        ],
    ];

    $syllabusImport = runImporter(CourseSyllabusImporter::class, $rows);
    $moduleImport = runImporter(CourseSyllabusModuleImporter::class, $rows);

    expect($syllabusImport['errors'])->toBe([])
        ->and($syllabusImport['results']['failed'])->toBe(0)
        ->and($syllabusImport['results']['successful'])->toBe(1)
        ->and($moduleImport['results']['failed'])->toBe(0)
        ->and($moduleImport['results']['successful'])->toBe(1);

    $courseSyllabus = CourseSyllabus::query()->where('code', 'CT-101')->first();
    expect($courseSyllabus)->not->toBeNull()
        ->and($courseSyllabus?->tenant_id)->toBe(1)
        ->and($courseSyllabus?->implementation_year)->toBe('2026');

    $module = CourseSyllabusModule::query()->where('code', 'MOD-CT-101')->first();
    expect($module)->not->toBeNull()
        ->and($module?->tenant_id)->toBe(1)
        ->and($module?->course_syllabus_id)->toBe($courseSyllabus?->id);
});

it('skips existing syllabus and module records with matching codes', function () {
    $context = makeSyllabusImportContext();

    $existingSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => 1,
        'institution_department_id' => $context['institutionDepartment']->id,
        'department_level_course_id' => $context['departmentLevelCourse']->id,
        'title' => 'Existing Syllabus',
        'code' => 'CT-102',
        'implementation_year' => '2025',
        'status' => 'active',
    ]);

    $existingModule = CourseSyllabusModule::query()->create([
        'tenant_id' => 1,
        'course_syllabus_id' => $existingSyllabus->id,
        'title' => 'Existing Module',
        'code' => 'MOD-CT-102',
        'shared' => false,
    ]);

    $rows = [
        [
            'DEPARTMENT' => 'Engineering',
            'LEVEL' => 'Level 1',
            'COURSE_TITLE' => 'Civil Technology',
            'COURSE_CODE' => 'CT-102',
            'IMPLEMENTATION_YEAR' => '2026',
            'MODULE_TITLE' => 'Updated Module Title',
            'MODULE_CODE' => 'MOD-CT-102',
        ],
    ];

    $syllabusImport = runImporter(CourseSyllabusImporter::class, $rows);
    $moduleImport = runImporter(CourseSyllabusModuleImporter::class, $rows);

    expect($syllabusImport['results']['failed'])->toBe(0)
        ->and($moduleImport['results']['failed'])->toBe(0);

    $existingSyllabus->refresh();
    $existingModule->refresh();

    expect($existingSyllabus->title)->toBe('Existing Syllabus')
        ->and($existingSyllabus->implementation_year)->toBe('2025')
        ->and($existingModule->title)->toBe('Existing Module');
});

it('fails syllabus row when relationship lookup cannot be resolved', function () {
    makeSyllabusImportContext();

    $import = runImporter(CourseSyllabusImporter::class, [
        [
            'DEPARTMENT' => 'Unknown Department',
            'LEVEL' => 'Level 1',
            'COURSE_TITLE' => 'Civil Technology',
            'COURSE_CODE' => 'CT-404',
            'IMPLEMENTATION_YEAR' => '2026',
            'MODULE_TITLE' => 'Unknown Module',
            'MODULE_CODE' => 'MOD-CT-404',
        ],
    ]);

    expect($import['results']['failed'])->toBe(1)
        ->and($import['results']['successful'])->toBe(0);

    expect(CourseSyllabus::query()->where('code', 'CT-404')->exists())->toBeFalse();
});
