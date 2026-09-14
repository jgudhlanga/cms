<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use App\Support\AcademicCalendars\CourseWorkTemplateSignature;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * @param  array<string, mixed>  $context
 * @return array<string, mixed>
 */
function securityTemplateData(array $context): array
{
    return app(CourseWorkImportTemplateService::class)->assembleForClassConfig(
        (int) $context['classConfig']->id,
        (int) $context['module']->id,
    );
}

/**
 * @param  array<string, mixed>  $data
 */
function securityTemplateSpreadsheet(array $data): Spreadsheet
{
    return IOFactory::load(storeCourseWorkImportFile($data)->getPathname());
}

/**
 * @param  array<string, mixed>  $context
 */
function securityTemplatePreview(array $context, UploadedFile $file): TestResponse
{
    return test()->actingAs($context['user'])->postJson(
        route('academic-calendars.department-classes.course-work-import.preview', courseWorkImportRouteParams($context)),
        ['module' => $context['module']->id, 'file' => $file],
    );
}

/**
 * @param  array<string, mixed>  $data
 * @param  array<string, mixed>  $changes
 * @return array{payload: string, signature: string}
 */
function securityTemplateResign(array $data, array $changes): array
{
    $payload = json_decode(base64_decode($data['signature']['payload']), true);
    unset($payload['v']);

    return CourseWorkTemplateSignature::sign([...$payload, ...$changes]);
}

beforeEach(function () {
    $this->context = createCourseWorkJsonApiContext();
    grantCourseWorkLifecyclePermissions($this->context['user'], ['import:course-work', 'create:course-work', 'update:course-work']);
    $this->context['assessmentType']->update(['weight_percent' => 20]);
});

test('the downloaded template locks everything except open mark cells and hides its signed metadata', function () {
    $spreadsheet = securityTemplateSpreadsheet(securityTemplateData($this->context));
    $marks = $spreadsheet->getSheetByName('Marks');
    $meta = $spreadsheet->getSheetByName(CourseWorkTemplateSignature::META_SHEET_TITLE);

    expect($marks->getProtection()->getSheet())->toBeTrue()
        ->and($marks->getProtection()->getPassword())->not->toBe('')
        ->and($marks->getStyle('A8')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED)
        ->and($marks->getStyle('B8')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED)
        ->and($marks->getStyle('E6')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED)
        ->and($marks->getStyle('E8')->getProtection()->getLocked())->toBe(Protection::PROTECTION_UNPROTECTED)
        ->and($marks->getCell('E8')->getDataValidation()->getType())->toBe(DataValidation::TYPE_WHOLE)
        ->and($marks->getCell('E8')->getDataValidation()->getFormula2())->toBe('100')
        ->and($marks->getRowDimension(7)->getVisible())->toBeFalse()
        ->and($meta)->toBeInstanceOf(Worksheet::class)
        ->and($meta->getSheetState())->toBe(Worksheet::SHEETSTATE_VERYHIDDEN)
        ->and($spreadsheet->getSecurity()->getLockStructure())->toBeTrue();
});

test('assessment columns whose window has closed are locked in the template', function () {
    config(['coursework.require_assessment_calendar' => true]);

    AssessmentCalendar::factory()->create([
        'tenant_id' => $this->context['tenant']->id,
        'assessment_type_id' => $this->context['assessmentType']->id,
        'academic_calendar_id' => $this->context['studentEnrolment']->academic_calendar_id,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => now()->subDay()->toDateString(),
    ]);

    $data = securityTemplateData($this->context);
    $marks = securityTemplateSpreadsheet($data)->getSheetByName('Marks');

    expect($data['editableAssessmentTypeIds'])->toBe([])
        ->and($data['closedAssessments'][0]['message'])->toContain('Due date passed')
        ->and($marks->getStyle('E8')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED);
});

test('mark-only templates unlock the mark column only', function () {
    $this->context['module']->update(['capture_mark_only' => true]);

    $marks = securityTemplateSpreadsheet(securityTemplateData($this->context))->getSheetByName('Marks');

    expect($marks->getStyle('A7')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED)
        ->and($marks->getStyle('E7')->getProtection()->getLocked())->toBe(Protection::PROTECTION_UNPROTECTED)
        ->and($marks->getStyle('F7')->getProtection()->getLocked())->toBe(Protection::PROTECTION_PROTECTED)
        ->and($marks->getCell('E7')->getDataValidation()->getType())->toBe(DataValidation::TYPE_WHOLE);
});

test('an untouched signed template still previews successfully', function () {
    $data = securityTemplateData($this->context);
    setCourseWorkWideImportMark($data, (int) $this->context['assessmentType']->id, 70);

    securityTemplatePreview($this->context, storeCourseWorkImportFile($data))
        ->assertSuccessful()
        ->assertJsonPath('summary.creates', 1);
});

test('files without the signed template metadata are rejected', function () {
    $data = securityTemplateData($this->context);
    setCourseWorkWideImportMark($data, (int) $this->context['assessmentType']->id, 70);
    unset($data['signature']);

    securityTemplatePreview($this->context, storeCourseWorkImportFile($data))
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

test('a forged signature is rejected', function () {
    $data = securityTemplateData($this->context);
    $data['signature']['signature'] = str_repeat('0', 64);

    securityTemplatePreview($this->context, storeCourseWorkImportFile($data))
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

test('a template issued for another module is rejected', function () {
    $data = securityTemplateData($this->context);
    $data['signature'] = securityTemplateResign($data, ['moduleId' => (int) $this->context['module']->id + 1000]);

    securityTemplatePreview($this->context, storeCourseWorkImportFile($data))
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

test('templates older than the allowed age are rejected', function () {
    $data = securityTemplateData($this->context);
    $file = storeCourseWorkImportFile($data);

    $this->travel((int) config('coursework.template_ttl_days') + 1)->days();

    securityTemplatePreview($this->context, $file)
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

test('student rows changed after download are rejected', function () {
    $data = securityTemplateData($this->context);
    $data['rows'][0]['studentNumber'] = 'FORGED-0001';

    securityTemplatePreview($this->context, storeCourseWorkImportFile($data))
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});

test('csv uploads are rejected', function () {
    $file = UploadedFile::fake()->createWithContent('marks.csv', "STUDENT_ENROLMENT_ID,MARK\n1,70\n");

    securityTemplatePreview($this->context, $file)
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');
});
