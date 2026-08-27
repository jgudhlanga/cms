<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\CourseWorkAuditEventEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkImportLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Assessments\MissingMarksEscalation;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
use App\Notifications\Assessments\MissingMarksNotification;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use App\Services\Assessments\AssessmentCalendarWindowService;
use App\Services\Students\StudentPortalDashboardService;
use Carbon\Carbon;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->seed(ClassMetaDataTypeSeeder::class);
    seedDashboardTestRoles();
    Notification::fake();
});

afterEach(function () {
    Carbon::setTestNow();
});

it('runs course work from assessment types through capture, import, notifications, escalation, and lock', function () {
    $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
    $context['assessmentType']->delete();

    $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
    prepareLecturerCalendar($context);
    AcademicCalendar::query()->whereKey($calendarId)->update([
        'opening_date' => now()->subMonths(2)->toDateString(),
        'closing_date' => now()->addMonths(6)->toDateString(),
    ]);

    // 1. Assessment types via HTTP
    $testType = storeAssessmentTypeViaHttp($context['admin'], [
        'name' => 'Lifecycle Test '.uniqid(),
        'modes_of_study' => [$context['modeOfStudy']->id],
        'description' => 'Primary continuous assessment',
    ]);
    $testType->update(['weight_percent' => 40]);

    $assignmentType = storeAssessmentTypeViaHttp($context['admin'], [
        'name' => 'Lifecycle Assignment '.uniqid(),
        'modes_of_study' => [$context['modeOfStudy']->id],
        'description' => 'Secondary continuous assessment',
    ]);
    $assignmentType->update(['weight_percent' => 20]);

    // 2. Assessment calendars via HTTP
    $endDate = now()->startOfDay()->addDays(10);
    $calendarPayload = [
        'academic_calendar_id' => $calendarId,
        'start_date' => now()->subDays(20)->toDateString(),
        'end_date' => $endDate->toDateString(),
        'first_notification_days_before' => 10,
        'second_notification_days_before' => 5,
        'due_notification_days_before' => 0,
        'type' => AcademicCalendarTypeEnum::SEMESTER->value,
    ];

    $testCalendar = storeAssessmentCalendarViaHttp($context['admin'], $testType, $calendarPayload);
    $assignmentCalendar = storeAssessmentCalendarViaHttp($context['admin'], $assignmentType, $calendarPayload);

    expect($testCalendar->first_notification_date?->toDateString())
        ->toBe($endDate->copy()->subDays(10)->toDateString())
        ->and($testCalendar->second_notification_date?->toDateString())
        ->toBe($endDate->copy()->subDays(5)->toDateString())
        ->and($testCalendar->due_notification_date?->toDateString())
        ->toBe($endDate->toDateString());

    // 3. Windows visible and open
    $windows = app(AssessmentCalendarWindowService::class)
        ->windowsForAcademicCalendar($calendarId, [$context['modeOfStudy']->id]);

    expect($windows)->toHaveCount(2);
    expect(collect($windows)->every(fn (array $window): bool => $window['isOpen'] === true))->toBeTrue();
    expect(collect($windows)->sum('missingCount'))->toBeGreaterThan(0);

    $this->actingAs($context['lecturerUser'])
        ->get(route('teaching.classes.show', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'academic_calendar_id' => $calendarId,
        ]))
        ->assertSuccessful();

    // 4. Manual capture for Test type
    jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
        'studentEnrolmentId' => $context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $context['module']->id,
        'assessmentTypeId' => $testType->id,
        'mark' => 72,
        'remark' => 'Solid test',
    ])->assertCreated();

    $testMark = CourseWorkMark::query()
        ->where('assessment_type_id', $testType->id)
        ->first();

    expect($testMark)->not->toBeNull()
        ->and($testMark->mark)->toBe(72);

    expect(CourseWorkAuditLog::query()
        ->where('course_work_mark_id', $testMark->id)
        ->where('event', CourseWorkAuditEventEnum::Created)
        ->count())->toBe(1);

    Sanctum::actingAs($context['lecturerUser']);
    $partialTree = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            'academic_calendar_id' => $calendarId,
        ]));

    $partialTree->assertSuccessful();
    $studentNode = collect($partialTree->json('meta.syllabi.0.modules.0.students'))
        ->firstWhere('studentEnrolmentId', $context['studentEnrolment']->id);

    expect($studentNode['aggregation']['isComplete'] ?? null)->toBeFalse()
        ->and($studentNode['aggregation']['courseWorkTotal60'] ?? null)->toBeNull();

    // 5. Import remaining Assignment marks
    $templateService = app(CourseWorkImportTemplateService::class);
    $importData = $templateService->assembleForClassConfig(
        (int) $context['academicCalendarClass']->class_config_id,
        (int) $context['module']->id,
    );
    setCourseWorkWideImportMark($importData, (int) $assignmentType->id, 80);
    $file = storeCourseWorkImportFile($importData);
    courseWorkImportPreviewAndProcess($file, $context, (int) $context['module']->id, $context['admin']);

    expect(CourseWorkMark::query()->where('assessment_type_id', $assignmentType->id)->value('mark'))->toBe(80);
    expect(CourseWorkImportLog::query()->count())->toBe(1);

    Sanctum::actingAs($context['lecturerUser']);
    $completeTree = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.tree', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            'academic_calendar_id' => $calendarId,
        ]));

    $completeStudent = collect($completeTree->json('meta.syllabi.0.modules.0.students'))
        ->firstWhere('studentEnrolmentId', $context['studentEnrolment']->id);

    expect($completeStudent['aggregation']['isComplete'])->toBeTrue()
        ->and($completeStudent['aggregation']['courseWorkTotal60'])->toBe(45);

    // 6. Marksheet + student pages
    $this->actingAs($context['admin'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet', courseWorkMarksheetRouteParams($context)))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/academicCalendars/DepartmentAcademicCalendarClassConfigCourseWorkMarksheet'));

    $this->actingAs($context['admin'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet.export', [
            ...courseWorkMarksheetRouteParams($context),
            'module' => $context['module']->id,
            'format' => 'xlsx',
        ]))
        ->assertSuccessful();

    $pdfResponse = $this->actingAs($context['admin'])
        ->get(route('academic-calendars.department-classes.course-work-marksheet.export', [
            ...courseWorkMarksheetRouteParams($context),
            'module' => $context['module']->id,
            'format' => 'pdf',
        ]));
    $pdfResponse->assertSuccessful();
    expect($pdfResponse->headers->get('content-type'))->toContain('pdf');

    $this->actingAs($context['admin'])
        ->get(route('academic-calendars.department-classes.student-course-work', [
            'institution_department' => $context['institutionDepartment']->id,
            'calendar_year' => $context['classConfig']->calendar_year,
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'student_enrolment' => $context['studentEnrolment']->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/academicCalendars/DepartmentAcademicCalendarClassStudentCourseWork')
            ->where('student.studentEnrolmentId', $context['studentEnrolment']->id));

    $this->actingAs($context['lecturerUser'])
        ->get(route('teaching.classes.student-course-work', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'student_enrolment' => $context['studentEnrolment']->id,
            'academic_calendar_id' => $calendarId,
        ]))
        ->assertSuccessful();

    // 7. Audit trail
    Sanctum::actingAs($context['admin']);
    $auditResponse = $this->jsonApi()
        ->get(route('v1.json.course-work-marks.auditLogs', [
            'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
        ]));

    $auditResponse->assertSuccessful();
    $events = collect($auditResponse->json('meta.logs'))->pluck('event')->all();
    expect($events)->toContain(CourseWorkAuditEventEnum::Created->value);

    // 8. Notifications — complete means nothing sent; then delete Assignment and escalate tiers
    travelToNotificationTier($assignmentCalendar, MissingMarksNotificationTierEnum::First);
    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    Notification::assertNothingSent();

    $assignmentMark = CourseWorkMark::query()
        ->where('assessment_type_id', $assignmentType->id)
        ->firstOrFail();

    grantCourseWorkLifecyclePermissions($context['lecturerUser'], ['delete:course-work']);
    jsonApiDestroyCourseWorkMark($context['lecturerUser'], (int) $assignmentMark->id, $context)
        ->assertNoContent();

    travelToNotificationTier($assignmentCalendar, MissingMarksNotificationTierEnum::First);
    Notification::fake();
    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    Notification::assertNotSentTo($context['vp'], MissingMarksNotification::class);
    expect(AssessmentCalendarNotificationDispatch::query()
        ->where('assessment_calendar_id', $assignmentCalendar->id)
        ->where('tier', MissingMarksNotificationTierEnum::First)
        ->exists())->toBeTrue();

    travelToNotificationTier($assignmentCalendar, MissingMarksNotificationTierEnum::Second);
    Notification::fake();
    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    Notification::assertSentTo($context['vp'], MissingMarksNotification::class);

    travelToNotificationTier($assignmentCalendar, MissingMarksNotificationTierEnum::Due);
    Notification::fake();
    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    Notification::assertSentTo($context['vp'], MissingMarksNotification::class);
    Notification::assertNotSentTo($context['lecturerUser'], MissingMarksNotification::class);

    $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();
    Notification::assertSentToTimes($context['vp'], MissingMarksNotification::class, 1);

    // 9. VP report, remind, escalate
    Carbon::setTestNow($endDate->copy()->subDays(3)->startOfDay()->addHours(9));
    Notification::fake();

    $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.index', [
            'academic_calendar_id' => $calendarId,
            'assessment_type_id' => $assignmentType->id,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/assessments/MissingMarksReport')
            ->has('rows', 1)
            ->where('rows.0.incompleteCount', 1));

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.remind'), [
            'assessment_calendar_id' => $assignmentCalendar->id,
        ])
        ->assertRedirect();

    Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
    expect(AssessmentCalendarNotificationDispatch::query()
        ->where('assessment_calendar_id', $assignmentCalendar->id)
        ->where('tier', MissingMarksNotificationTierEnum::First)
        ->count())->toBe(1);

    $exportResponse = $this->actingAs($context['vp'])
        ->get(route('missing-marks-report.export', [
            'academic_calendar_id' => $calendarId,
            'assessment_type_id' => $assignmentType->id,
        ]));
    $exportResponse->assertSuccessful();
    expect($exportResponse->headers->get('content-type'))->toContain('spreadsheet');

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $assignmentCalendar->id,
            'notes' => 'Please follow up with the lecturer',
        ])
        ->assertRedirect();

    Notification::assertSentTo($context['principal'], MissingMarksNotification::class);
    expect(MissingMarksEscalation::query()
        ->where('assessment_calendar_id', $assignmentCalendar->id)
        ->exists())->toBeTrue();

    $this->actingAs($context['vp'])
        ->post(route('missing-marks-report.escalate'), [
            'assessment_calendar_id' => $assignmentCalendar->id,
            'notes' => 'Again',
        ])
        ->assertRedirect();

    Notification::assertSentToTimes($context['principal'], MissingMarksNotification::class, 1);

    // 10. Student portal notices
    Carbon::setTestNow($endDate->copy()->subDays(4)->startOfDay()->addHours(10));
    $portalUser = $context['studentUser'];
    Permission::findOrCreate('viewOwnDashboard:students', 'web');
    Permission::findOrCreate('manageOwnStudentApplicationDetails:students', 'web');
    $portalUser->givePermissionTo(['viewOwnDashboard:students', 'manageOwnStudentApplicationDetails:students']);

    $noticesWhileMissing = app(StudentPortalDashboardService::class)->build($portalUser);
    expect($noticesWhileMissing['notices'])->not->toBeEmpty()
        ->and($noticesWhileMissing['notices'][0]['message'])->toContain($context['module']->title)
        ->and($noticesWhileMissing['notices'][0]['message'])->toContain($assignmentType->name)
        ->and(strtolower($noticesWhileMissing['notices'][0]['message']))->not->toContain('lecturer');

    jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
        'studentEnrolmentId' => $context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $context['module']->id,
        'assessmentTypeId' => $assignmentType->id,
        'mark' => 80,
    ])->assertCreated();

    $noticesAfterCapture = app(StudentPortalDashboardService::class)->build($portalUser);
    expect($noticesAfterCapture['notices'])->toBe([]);

    // 11. Lock after end date
    Carbon::setTestNow($endDate->copy()->addDay()->startOfDay()->addHours(9));

    $this->actingAs($context['lecturerUser'])
        ->get(route('teaching.classes.show', [
            'academic_calendar_class' => $context['academicCalendarClass']->id,
            'academic_calendar_id' => $calendarId,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('classDetail.modules.0.courseWorkLock.allAssessmentTypesLocked', true)
            ->where('classDetail.modules.0.courseWorkLock.hasEditableCourseWork', false));

    jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
        'studentEnrolmentId' => $context['studentEnrolment']->id,
        'courseSyllabusModuleId' => $context['module']->id,
        'assessmentTypeId' => $testType->id,
        'mark' => 90,
    ])->assertStatus(422);

    expect(CourseWorkMark::query()->where('assessment_type_id', $testType->id)->value('mark'))->toBe(72);
});
