<?php

use App\DTO\Assessments\EffectiveAssessmentWindow;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\CourseWorkExtensionStatusEnum;
use App\Enums\Assessments\AssessmentWindowStatusEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\CourseWorkCaptureExtension;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\DepartmentAssessmentCalendar;
use App\Models\Institution\AssessmentType;
use App\Services\Assessments\EffectiveAssessmentWindowResolver;

/**
 * @param  array<string, mixed>  $context
 */
function windowResolverGlobalCalendar(
    array $context,
    string $startDate,
    string $endDate,
    ?AssessmentType $assessmentType = null,
    ?AcademicCalendar $academicCalendar = null,
    string $type = 'semester',
): AssessmentCalendar {
    return AssessmentCalendar::query()->create([
        'tenant_id' => $context['tenant']->id,
        'assessment_type_id' => ($assessmentType ?? $context['assessmentType'])->id,
        'academic_calendar_id' => ($academicCalendar ?? $context['calendar'])->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'type' => $type,
    ]);
}

/**
 * @param  array<string, mixed>  $context
 * @param  array<string, mixed>  $overrides
 */
function windowResolverExtension(array $context, array $overrides = []): CourseWorkCaptureExtension
{
    return CourseWorkCaptureExtension::query()->create([
        'tenant_id' => $context['tenant']->id,
        'academic_calendar_class_id' => $context['academicCalendarClass']->id,
        'course_syllabus_module_id' => $context['module']->id,
        'assessment_type_id' => $context['assessmentType']->id,
        'institution_department_id' => $context['institutionDepartment']->id,
        'requested_by' => $context['user']->id,
        'reason' => 'Practical results were delayed by a laboratory outage.',
        'requested_until' => now()->addDays(3)->toDateString(),
        'status' => CourseWorkExtensionStatusEnum::Approved->value,
        'decided_by' => $context['user']->id,
        'approved_until' => now()->addDays(3)->toDateString(),
        'decided_at' => now(),
        ...$overrides,
    ]);
}

beforeEach(function () {
    // The suite relaxes the "no calendar means closed" rule for legacy tests; these tests assert the real default.
    config(['coursework.require_assessment_calendar' => true]);
    $this->context = createCourseWorkJsonApiContext();
    $this->resolver = app(EffectiveAssessmentWindowResolver::class);
    $this->today = now()->startOfDay();
    $this->academicCalendarId = (int) $this->context['calendar']->id;
    $this->departmentId = (int) $this->context['institutionDepartment']->id;
    $this->modeId = (int) $this->context['modeOfStudy']->id;
    $this->typeId = (int) $this->context['assessmentType']->id;
    $this->classId = (int) $this->context['academicCalendarClass']->id;
    $this->moduleId = (int) $this->context['module']->id;
});

test('an assessment type without a calendar is not configured and closed for capture by default', function () {
    $window = $this->resolver->windowFor(
        $this->academicCalendarId,
        $this->departmentId,
        $this->modeId,
        $this->typeId,
        today: $this->today,
    );

    expect($window->status)->toBe(AssessmentWindowStatusEnum::NotConfigured)
        ->and($window->isCaptureAllowed())->toBeFalse();

    config(['coursework.require_assessment_calendar' => false]);

    expect($window->isCaptureAllowed())->toBeTrue();
});

test('a global window is not open before its start date, open inside it and closed after its end date', function () {
    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->addDays(2)->toDateString(),
        $this->today->copy()->addDays(10)->toDateString(),
    );

    $window = fn ($day) => $this->resolver->windowFor(
        $this->academicCalendarId,
        $this->departmentId,
        $this->modeId,
        $this->typeId,
        today: $day,
    );

    $before = $window($this->today);
    $lastDay = $window($this->today->copy()->addDays(10));
    $after = $window($this->today->copy()->addDays(11));

    expect($before->status)->toBe(AssessmentWindowStatusEnum::NotOpen)
        ->and($before->isCaptureAllowed())->toBeFalse()
        ->and($lastDay->status)->toBe(AssessmentWindowStatusEnum::Open)
        ->and($lastDay->source)->toBe(EffectiveAssessmentWindow::SOURCE_GLOBAL)
        ->and($lastDay->isCaptureAllowed())->toBeTrue()
        ->and($after->status)->toBe(AssessmentWindowStatusEnum::Closed)
        ->and($after->isCaptureAllowed())->toBeFalse()
        ->and($after->message())->toContain('Due date passed');
});

test('a department calendar narrows the global window for that department only', function () {
    $globalCalendar = windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(5)->toDateString(),
        $this->today->copy()->addDays(20)->toDateString(),
    );

    DepartmentAssessmentCalendar::query()->create([
        'tenant_id' => $this->context['tenant']->id,
        'assessment_calendar_id' => $globalCalendar->id,
        'institution_department_id' => $this->departmentId,
        'start_date' => $this->today->copy()->subDays(5)->toDateString(),
        'end_date' => $this->today->copy()->subDay()->toDateString(),
    ]);

    $departmentWindow = $this->resolver->windowFor(
        $this->academicCalendarId,
        $this->departmentId,
        $this->modeId,
        $this->typeId,
        today: $this->today,
    );
    $otherDepartmentWindow = $this->resolver->windowFor(
        $this->academicCalendarId,
        $this->departmentId + 100000,
        $this->modeId,
        $this->typeId,
        today: $this->today,
    );

    expect($departmentWindow->status)->toBe(AssessmentWindowStatusEnum::Closed)
        ->and($departmentWindow->source)->toBe(EffectiveAssessmentWindow::SOURCE_DEPARTMENT)
        ->and($departmentWindow->endDate)->toBe($this->today->copy()->subDay()->toDateString())
        ->and($departmentWindow->globalEndDate)->toBe($this->today->copy()->addDays(20)->toDateString())
        ->and($otherDepartmentWindow->status)->toBe(AssessmentWindowStatusEnum::Open)
        ->and($otherDepartmentWindow->source)->toBe(EffectiveAssessmentWindow::SOURCE_GLOBAL);
});

test('term calendars resolve from the class enrolments instead of the current semester', function () {
    $term = AcademicCalendar::query()->create([
        'calendar_year' => '2026',
        'type' => AcademicCalendarTypeEnum::TERM->value,
        'opening_date' => $this->today->copy()->subDays(60)->toDateString(),
        'closing_date' => $this->today->copy()->addDays(60)->toDateString(),
    ]);
    $this->context['studentEnrolment']->update(['academic_calendar_id' => $term->id]);

    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(30)->toDateString(),
        $this->today->copy()->subDays(2)->toDateString(),
        academicCalendar: $term,
        type: AcademicCalendarTypeEnum::TERM->value,
    );

    $academicCalendarId = $this->resolver->academicCalendarIdForClass($this->classId);
    $window = $this->resolver->windowFor(
        (int) $academicCalendarId,
        $this->departmentId,
        $this->modeId,
        $this->typeId,
        today: $this->today,
    );

    expect($academicCalendarId)->toBe((int) $term->id)
        ->and($this->resolver->academicCalendarIdForClassConfig((int) $this->context['classConfig']->id))->toBe((int) $term->id)
        ->and($window->status)->toBe(AssessmentWindowStatusEnum::Closed);
});

test('an approved extension reopens a closed window only for its class, module and assessment type until it expires', function () {
    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(20)->toDateString(),
        $this->today->copy()->subDay()->toDateString(),
    );
    windowResolverExtension($this->context, [
        'approved_until' => $this->today->copy()->addDays(3)->toDateString(),
    ]);

    $extended = $this->resolver->windowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId, $this->typeId,
        $this->classId, $this->moduleId, $this->today,
    );
    $otherModule = $this->resolver->windowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId, $this->typeId,
        $this->classId, $this->moduleId + 100000, $this->today,
    );
    $expired = $this->resolver->windowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId, $this->typeId,
        $this->classId, $this->moduleId, $this->today->copy()->addDays(4),
    );

    expect($extended->status)->toBe(AssessmentWindowStatusEnum::Extended)
        ->and($extended->isCaptureAllowed())->toBeTrue()
        ->and($extended->extendedUntil)->toBe($this->today->copy()->addDays(3)->toDateString())
        ->and($otherModule->status)->toBe(AssessmentWindowStatusEnum::Closed)
        ->and($expired->status)->toBe(AssessmentWindowStatusEnum::Closed);
});

test('pending, rejected and revoked extensions never reopen capture', function () {
    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(20)->toDateString(),
        $this->today->copy()->subDay()->toDateString(),
    );
    windowResolverExtension($this->context, ['status' => CourseWorkExtensionStatusEnum::Pending->value]);
    windowResolverExtension($this->context, ['status' => CourseWorkExtensionStatusEnum::Rejected->value]);
    windowResolverExtension($this->context, ['revoked_at' => now(), 'status' => CourseWorkExtensionStatusEnum::Revoked->value]);

    $window = $this->resolver->windowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId, $this->typeId,
        $this->classId, $this->moduleId, $this->today,
    );

    expect($window->status)->toBe(AssessmentWindowStatusEnum::Closed);
});

test('mark-only modules are open while any applicable window is open and reopen only through a module extension', function () {
    $secondType = AssessmentType::factory()->create([
        'tenant_id' => $this->context['tenant']->id,
        'name' => 'Second Assessment '.uniqid(),
        'modes_of_study' => [$this->modeId],
    ]);

    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(10)->toDateString(),
        $this->today->copy()->addDays(5)->toDateString(),
    );
    windowResolverGlobalCalendar(
        $this->context,
        $this->today->copy()->subDays(10)->toDateString(),
        $this->today->copy()->subDay()->toDateString(),
        $secondType,
    );

    $open = $this->resolver->moduleMarkWindowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId,
        $this->classId, $this->moduleId, $this->today,
    );
    $closed = $this->resolver->moduleMarkWindowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId,
        $this->classId, $this->moduleId, $this->today->copy()->addDays(6),
    );

    windowResolverExtension($this->context, [
        'assessment_type_id' => null,
        'approved_until' => $this->today->copy()->addDays(8)->toDateString(),
    ]);

    $extended = app(EffectiveAssessmentWindowResolver::class)->moduleMarkWindowFor(
        $this->academicCalendarId, $this->departmentId, $this->modeId,
        $this->classId, $this->moduleId, $this->today->copy()->addDays(6),
    );

    expect($open->status)->toBe(AssessmentWindowStatusEnum::Open)
        ->and($open->endDate)->toBe($this->today->copy()->addDays(5)->toDateString())
        ->and($closed->status)->toBe(AssessmentWindowStatusEnum::Closed)
        ->and($extended->status)->toBe(AssessmentWindowStatusEnum::Extended)
        ->and($extended->isCaptureAllowed())->toBeTrue();
});
