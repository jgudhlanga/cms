<?php

use App\Exports\Institution\CourseSyllabusImportTemplateExport;
use App\Importers\Institution\CourseSyllabusImporter;
use App\Models\AcademicCalendars\AcademicYearOption;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusImportLog;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Users\User;
use App\Services\Institution\CourseSyllabusImportTemplateService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;

require_once __DIR__.'/CourseSyllabusImportersTest.php';

/**
 * @param  array<string, string|null>  $overrides
 * @return list<string|null>
 */
function syllabusImportWebRowValues(array $overrides = []): array
{
    $suffix = substr(uniqid(), -4);

    $defaults = [
        'LEVEL' => 'Level 1',
        'COURSE_TITLE' => 'Civil Technology',
        'COURSE_CODE' => "CT/26/{$suffix}",
        'SEMESTER' => 'Semester 1',
        'MODULE_TITLE' => 'Module Intro',
        'MODULE_CODE' => "MOD-CT-{$suffix}",
    ];

    $merged = array_merge($defaults, $overrides);

    return array_map(
        static fn (string $column): ?string => $merged[$column] ?? null,
        CourseSyllabusImporter::WEB_COLUMNS,
    );
}

/**
 * @param  list<list<string|null>>  $rows
 */
function storeSyllabusImportFile(array $rows, int $institutionDepartmentId): UploadedFile
{
    $templateService = app(CourseSyllabusImportTemplateService::class);
    $data = $templateService->assemble($institutionDepartmentId);
    $data['rows'] = $rows;

    $relativePath = 'test-syllabus-import-'.uniqid().'.xlsx';
    Excel::store(new CourseSyllabusImportTemplateExport($data), $relativePath, 'local');

    return new UploadedFile(storage_path('app/'.$relativePath), 'syllabus-import.xlsx', null, null, true);
}

/**
 * @return array{user: User, institutionDepartmentId: int}
 */
function makeSyllabusWebImportContext(): array
{
    $context = makeSyllabusImportContext();
    Permission::findOrCreate('import:course-syllabuses', 'web');

    $user = User::factory()->create(['tenant_id' => 1]);
    $user->givePermissionTo('import:course-syllabuses');

    return [
        'user' => $user,
        'institutionDepartmentId' => (int) $context['institutionDepartment']->id,
        'institutionDepartment' => $context['institutionDepartment'],
        'departmentLevelCourseId' => (int) $context['departmentLevelCourse']->id,
    ];
}

function syllabusImportPreviewAndProcess(UploadedFile $file, int $institutionDepartmentId, User $user): string
{
    test()->actingAs($user);

    $previewResponse = test()->post(route('department-course-syllabuses.import.preview', [
        'institution_department' => $institutionDepartmentId,
    ]), [
        'file' => $file,
    ]);

    $previewResponse->assertSuccessful();

    $previewToken = $previewResponse->json('previewToken');
    expect($previewToken)->not->toBeEmpty();

    test()->post(route('department-course-syllabuses.import.process', [
        'institution_department' => $institutionDepartmentId,
    ]), [
        'preview_token' => $previewToken,
    ])->assertRedirect(route('department-course-syllabuses.import', [
        'institution_department' => $institutionDepartmentId,
    ]));

    return $previewToken;
}

it('syllabus import page requires import permission', function () {
    $importContext = makeSyllabusImportContext();
    Permission::findOrCreate('viewAny:course-syllabuses', 'web');

    $user = User::factory()->create(['tenant_id' => 1]);
    $user->givePermissionTo('viewAny:course-syllabuses');

    $this->actingAs($user)
        ->get(route('department-course-syllabuses.import', [
            'institution_department' => (int) $importContext['institutionDepartment']->id,
        ]))
        ->assertForbidden();
});

it('authorized user can view syllabus import page', function () {
    $context = makeSyllabusWebImportContext();

    $this->actingAs($context['user'])
        ->get(route('department-course-syllabuses.import', [
            'institution_department' => $context['institutionDepartmentId'],
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/syllabus/Import')
            ->has('institutionDepartment'));
});

it('syllabus import template requires import permission', function () {
    $context = makeSyllabusWebImportContext();
    Permission::findOrCreate('viewAny:course-syllabuses', 'web');
    $context['user']->revokePermissionTo('import:course-syllabuses');
    $context['user']->givePermissionTo('viewAny:course-syllabuses');

    $this->actingAs($context['user'])
        ->get(route('department-course-syllabuses.import.template', [
            'institution_department' => $context['institutionDepartmentId'],
        ]))
        ->assertForbidden();
});

it('downloads syllabus import template with web columns', function () {
    $context = makeSyllabusWebImportContext();

    $response = $this->actingAs($context['user'])
        ->get(route('department-course-syllabuses.import.template', [
            'institution_department' => $context['institutionDepartmentId'],
        ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');
});

it('previews valid syllabus import rows', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([syllabusImportWebRowValues()], $context['institutionDepartmentId']);

    $response = $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('summary.failed', 0)
        ->assertJsonPath('summary.syllabusCreates', 1)
        ->assertJsonPath('summary.moduleCreates', 1)
        ->assertJsonStructure([
            'previewToken',
            'fileStats' => [
                'totalRows',
                'uniqueCourseCodes',
                'uniqueModuleCodes',
                'uniqueModuleRecords',
                'duplicateModuleCodeGroups',
                'extraRowsFromDuplicateModuleCodes',
                'moduleRows',
                'moduleSkipRows',
            ],
            'lookups' => ['levels', 'courses', 'levelCourses', 'semesters'],
            'rows' => [
                [
                    'rowNumber',
                    'courseCode',
                    'syllabusExists',
                    'moduleExists',
                    'moduleCodeRepeatedInFile',
                    'moduleCodeOccurrencesInFile',
                    'syllabusAction',
                    'moduleAction',
                    'syllabusErrors',
                    'moduleErrors',
                ],
            ],
        ]);
});

it('previews failed module rows for unknown semester', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'SEMESTER' => 'Not A Real Period',
        ]),
    ], $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.failed', 1)
        ->assertJsonPath('summary.moduleFails', 1);
});

it('previews failed syllabus import rows for unknown level course link', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'LEVEL' => 'Unknown Level',
        ]),
    ], $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.failed', 1)
        ->assertJsonPath('summary.syllabusCreates', 0);
});

it('processes syllabus import and creates syllabus and module records', function () {
    $context = makeSyllabusWebImportContext();
    $row = syllabusImportWebRowValues();
    $file = storeSyllabusImportFile([$row], $context['institutionDepartmentId']);

    syllabusImportPreviewAndProcess($file, $context['institutionDepartmentId'], $context['user']);

    $courseCode = (string) array_combine(CourseSyllabusImporter::WEB_COLUMNS, $row)['COURSE_CODE'];
    $moduleCode = (string) array_combine(CourseSyllabusImporter::WEB_COLUMNS, $row)['MODULE_CODE'];

    $courseSyllabus = CourseSyllabus::query()->where('code', $courseCode)->first();
    expect($courseSyllabus)->not->toBeNull()
        ->and($courseSyllabus?->implementation_year)->toBe('2026');

    $module = CourseSyllabusModule::query()->where('code', $moduleCode)->first();
    $semesterOneId = (int) AcademicYearOption::query()->where('slug', 'semester-1')->value('id');

    expect($module)->not->toBeNull()
        ->and($module?->title)->toBe('Module Intro')
        ->and($module?->course_syllabus_id)->toBe($courseSyllabus?->id)
        ->and($module?->academic_year_option_id)->toBe($semesterOneId);

    expect(CourseSyllabusImportLog::query()->count())->toBe(1);
});

it('updates duplicate syllabus and module records on import preview', function () {
    $context = makeSyllabusWebImportContext();

    $existingSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => 1,
        'institution_department_id' => $context['institutionDepartmentId'],
        'department_level_course_id' => $context['departmentLevelCourseId'],
        'title' => 'Existing Syllabus',
        'code' => 'CT/25/102',
        'implementation_year' => '2025',
        'status' => 'active',
    ]);

    CourseSyllabusModule::query()->create([
        'tenant_id' => 1,
        'course_syllabus_id' => $existingSyllabus->id,
        'title' => 'Existing Module',
        'code' => 'MOD-CT-25-102',
        'shared' => false,
    ]);

    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'COURSE_CODE' => 'CT/25/102',
            'MODULE_CODE' => 'MOD-CT-25-102',
            'MODULE_TITLE' => 'Updated Module Title',
            'SEMESTER' => 'Semester 1',
        ]),
    ], $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.syllabusUpdates', 1)
        ->assertJsonPath('summary.moduleUpdates', 1)
        ->assertJsonPath('summary.failed', 0);
});

it('processes import with preview row corrections', function () {
    $context = makeSyllabusWebImportContext();
    $row = syllabusImportWebRowValues([
        'SEMESTER' => 'Not A Real Period',
    ]);
    $file = storeSyllabusImportFile([$row], $context['institutionDepartmentId']);

    $previewResponse = $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.failed', 1);

    $previewToken = $previewResponse->json('previewToken');

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.process', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'preview_token' => $previewToken,
            'row_corrections' => [
                1 => ['semester' => 'Semester 1'],
            ],
        ])
        ->assertRedirect(route('department-course-syllabuses.import', [
            'institution_department' => $context['institutionDepartmentId'],
        ]));

    $moduleCode = (string) array_combine(CourseSyllabusImporter::WEB_COLUMNS, $row)['MODULE_CODE'];

    expect(CourseSyllabusModule::query()->where('code', $moduleCode)->exists())->toBeTrue();
});

it('processes import while excluding preview rows', function () {
    $context = makeSyllabusWebImportContext();
    $firstRow = syllabusImportWebRowValues();
    $secondRow = syllabusImportWebRowValues();
    $file = storeSyllabusImportFile([$firstRow, $secondRow], $context['institutionDepartmentId']);

    $previewResponse = $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 2);

    $previewToken = $previewResponse->json('previewToken');
    $excludedModuleCode = (string) array_combine(CourseSyllabusImporter::WEB_COLUMNS, $secondRow)['MODULE_CODE'];
    $includedModuleCode = (string) array_combine(CourseSyllabusImporter::WEB_COLUMNS, $firstRow)['MODULE_CODE'];

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.process', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'preview_token' => $previewToken,
            'excluded_row_numbers' => [2],
        ])
        ->assertRedirect(route('department-course-syllabuses.import', [
            'institution_department' => $context['institutionDepartmentId'],
        ]));

    expect(CourseSyllabusModule::query()->where('code', $includedModuleCode)->exists())->toBeTrue()
        ->and(CourseSyllabusModule::query()->where('code', $excludedModuleCode)->exists())->toBeFalse();
});

it('rejects expired preview token on process', function () {
    $context = makeSyllabusWebImportContext();

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.process', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'preview_token' => 'invalid-token',
        ])
        ->assertSessionHasErrors('preview_token');
});

it('rejects preview token from another user', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([syllabusImportWebRowValues()], $context['institutionDepartmentId']);

    $previewResponse = $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ]);

    $previewToken = $previewResponse->json('previewToken');

    $otherUser = User::factory()->create(['tenant_id' => 1]);
    $otherUser->givePermissionTo('import:course-syllabuses');

    $this->actingAs($otherUser)
        ->post(route('department-course-syllabuses.import.process', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'preview_token' => $previewToken,
        ])
        ->assertSessionHasErrors('preview_token');

    Cache::forget('course-syllabus-import-preview:'.$previewToken);
});

it('rejects process when preview contains failed rows', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'COURSE_TITLE' => 'Unknown Course',
        ]),
    ], $context['institutionDepartmentId']);

    $previewResponse = $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ]);

    $previewToken = $previewResponse->json('previewToken');

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.process', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'preview_token' => $previewToken,
        ])
        ->assertSessionHasErrors('preview_token');

    Cache::forget('course-syllabus-import-preview:'.$previewToken);
});

it('imports duplicate module codes when they belong to different course syllabuses', function () {
    $context = makeSyllabusWebImportContext();
    $sharedModuleCode = 'MOD-SHARED-'.uniqid();

    $rows = [
        syllabusImportWebRowValues([
            'COURSE_CODE' => 'CT/26/301',
            'MODULE_CODE' => $sharedModuleCode,
            'MODULE_TITLE' => 'National Studies',
        ]),
        syllabusImportWebRowValues([
            'COURSE_CODE' => 'CT/26/302',
            'MODULE_CODE' => $sharedModuleCode,
            'MODULE_TITLE' => 'National Studies',
        ]),
    ];

    $file = storeSyllabusImportFile($rows, $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.total', 2)
        ->assertJsonPath('summary.moduleCreates', 2)
        ->assertJsonPath('summary.failed', 0);

    syllabusImportPreviewAndProcess($file, $context['institutionDepartmentId'], $context['user']);

    expect(CourseSyllabusModule::query()->where('code', $sharedModuleCode)->count())->toBe(2);
});

it('preview includes file stats when module codes are reused across courses', function () {
    $context = makeSyllabusWebImportContext();
    $sharedModuleCode = 'MOD-SHARED-'.uniqid();

    $rows = [
        syllabusImportWebRowValues([
            'COURSE_CODE' => 'CT/26/401',
            'MODULE_CODE' => $sharedModuleCode,
            'MODULE_TITLE' => 'National Studies',
        ]),
        syllabusImportWebRowValues([
            'COURSE_CODE' => 'CT/26/402',
            'MODULE_CODE' => $sharedModuleCode,
            'MODULE_TITLE' => 'National Studies',
        ]),
    ];

    $file = storeSyllabusImportFile($rows, $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('fileStats.totalRows', 2)
        ->assertJsonPath('fileStats.uniqueCourseCodes', 2)
        ->assertJsonPath('fileStats.uniqueModuleCodes', 1)
        ->assertJsonPath('fileStats.uniqueModuleRecords', 2)
        ->assertJsonPath('fileStats.duplicateModuleCodeGroups', 1)
        ->assertJsonPath('fileStats.extraRowsFromDuplicateModuleCodes', 1)
        ->assertJsonPath('rows.0.moduleCodeRepeatedInFile', true)
        ->assertJsonPath('rows.1.moduleCodeRepeatedInFile', true);
});

it('previews dot-delimited course codes without failing', function () {
    $context = makeSyllabusWebImportContext();
    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'COURSE_CODE' => '553.23.CO.MO',
            'MODULE_CODE' => '553.23.M01',
        ]),
    ], $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('summary.failed', 0)
        ->assertJsonPath('summary.syllabusCreates', 1)
        ->assertJsonPath('summary.moduleCreates', 1);
});

it('previews existing syllabus and module when only delimiter style differs', function () {
    $context = makeSyllabusWebImportContext();

    $existingSyllabus = CourseSyllabus::query()->create([
        'tenant_id' => 1,
        'institution_department_id' => $context['institutionDepartmentId'],
        'department_level_course_id' => $context['departmentLevelCourseId'],
        'title' => 'Existing Syllabus',
        'code' => '553/23/CO/MO',
        'implementation_year' => '2023',
        'status' => 'active',
    ]);

    CourseSyllabusModule::query()->create([
        'tenant_id' => 1,
        'course_syllabus_id' => $existingSyllabus->id,
        'title' => 'Existing Module',
        'code' => '553/23/M01',
        'shared' => false,
    ]);

    $file = storeSyllabusImportFile([
        syllabusImportWebRowValues([
            'COURSE_CODE' => '553.23.CO.MO',
            'MODULE_CODE' => '553.23.M01',
            'MODULE_TITLE' => 'Updated Module Title',
            'SEMESTER' => 'Semester 1',
        ]),
    ], $context['institutionDepartmentId']);

    $this->actingAs($context['user'])
        ->post(route('department-course-syllabuses.import.preview', [
            'institution_department' => $context['institutionDepartmentId'],
        ]), [
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('rows.0.syllabusExists', true)
        ->assertJsonPath('rows.0.moduleExists', true)
        ->assertJsonPath('summary.syllabusUpdates', 1)
        ->assertJsonPath('summary.moduleUpdates', 1)
        ->assertJsonPath('summary.failed', 0);
});
