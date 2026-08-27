<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\CourseWorkAuditEventEnum;
use App\Enums\Assessments\MissingMarksNotificationTierEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\CourseWorkAuditLog;
use App\Models\AcademicCalendars\CourseWorkMark;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendar;
use App\Models\Institution\AssessmentCalendar\AssessmentCalendarNotificationDispatch;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Staff;
use App\Notifications\Assessments\MissingMarksNotification;
use App\Services\AcademicCalendars\CourseWorkAggregationService;
use App\Services\AcademicCalendars\CourseWorkImportTemplateService;
use App\Services\Assessments\AssessmentCalendarWindowService;
use App\Services\Assessments\MissingMarksQueryService;
use App\Support\AcademicCalendars\CourseWorkGradeBand;
use Carbon\Carbon;
use Database\Seeders\AcademicCalendars\ClassMetaDataTypeSeeder;
use Illuminate\Support\Facades\Cache;
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

describe('assessment types corners', function () {
    test('store rejects duplicate assessment type names', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $name = 'Unique Type '.uniqid();

        storeAssessmentTypeViaHttp($context['admin'], [
            'name' => $name,
            'modes_of_study' => [$context['modeOfStudy']->id],
        ]);

        $this->actingAs($context['admin'])
            ->post(route('assessment-types.store'), [
                'name' => $name,
                'modes_of_study' => [$context['modeOfStudy']->id],
            ])
            ->assertSessionHasErrors('name');
    });

    test('store rejects empty modes of study', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());

        $this->actingAs($context['admin'])
            ->post(route('assessment-types.store'), [
                'name' => 'No Modes '.uniqid(),
                'modes_of_study' => [],
            ])
            ->assertSessionHasErrors('modes_of_study');
    });

    test('mismatched mode of study is omitted from tree and missing marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $otherMode = ModeOfStudy::query()->create(['name' => 'Other Mode '.uniqid()]);
        $context['assessmentType']->update([
            'modes_of_study' => [$otherMode->id],
            'weight_percent' => 20,
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        Sanctum::actingAs($context['admin']);
        $tree = $this->jsonApi()
            ->get(route('v1.json.course-work-marks.tree', [
                'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            ]));

        $tree->assertSuccessful();
        $typeIds = collect($tree->json('meta.assessmentTypes'))->pluck('id')->all();
        expect($typeIds)->not->toContain($context['assessmentType']->id);

        $calendar = AssessmentCalendar::query()
            ->where('assessment_type_id', $context['assessmentType']->id)
            ->firstOrFail();

        expect(app(MissingMarksQueryService::class)->forCalendar($calendar))->toBe([]);
    });
});

describe('assessment calendar corners', function () {
    test('custom notification days drive matching tier and command dispatch', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->startOfDay()->addDays(7)->toDateString(),
            'first_notification_days_before' => 7,
            'second_notification_days_before' => 3,
            'due_notification_days_before' => 0,
        ]);

        travelToNotificationTier($calendar, MissingMarksNotificationTierEnum::First);
        $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();

        Notification::assertSentTo($context['lecturerUser'], MissingMarksNotification::class);
        expect(AssessmentCalendarNotificationDispatch::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->where('tier', MissingMarksNotificationTierEnum::First)
            ->exists())->toBeTrue();
    });

    test('calendar for another academic year does not lock current class', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendarId = prepareLecturerCalendar($context);

        $otherAcademicCalendar = AcademicCalendar::query()->create([
            'calendar_year' => '2025',
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'opening_date' => now()->subYear()->startOfYear()->toDateString(),
            'closing_date' => now()->subYear()->endOfYear()->toDateString(),
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $otherAcademicCalendar->id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subYear()->toDateString(),
            'end_date' => now()->subMonths(6)->toDateString(),
        ]);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 55,
        ], ['academic_calendar_id' => $calendarId])->assertCreated();
    });
});

describe('json api capture corners', function () {
    test('destroy writes deleted audit log', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $mark = CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 40,
            'created_by' => $context['lecturerUser']->id,
            'updated_by' => $context['lecturerUser']->id,
        ]);

        jsonApiDestroyCourseWorkMark($context['lecturerUser'], (int) $mark->id, $context)
            ->assertNoContent();

        expect(CourseWorkMark::query()->whereKey($mark->id)->exists())->toBeFalse();
        expect(CourseWorkAuditLog::query()
            ->where('course_work_mark_id', $mark->id)
            ->where('event', CourseWorkAuditEventEnum::Deleted)
            ->exists())->toBeTrue();
    });

    test('re-upsert restores soft-deleted mark and writes restored audit', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $mark = CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 40,
            'created_by' => $context['lecturerUser']->id,
            'updated_by' => $context['lecturerUser']->id,
        ]);
        $mark->delete();

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 66,
        ])->assertCreated();

        expect(CourseWorkMark::query()->whereKey($mark->id)->value('mark'))->toBe(66);
        expect(CourseWorkAuditLog::query()
            ->where('course_work_mark_id', $mark->id)
            ->where('event', CourseWorkAuditEventEnum::Restored)
            ->exists())->toBeTrue();
    });

    test('audit logs require permission and support class scope', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());

        CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 50,
            'created_by' => $context['admin']->id,
            'updated_by' => $context['admin']->id,
        ]);

        Sanctum::actingAs($context['lecturerUser']);
        $this->jsonApi()
            ->get(route('v1.json.course-work-marks.auditLogs', [
                'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            ]))
            ->assertForbidden();

        Sanctum::actingAs($context['admin']);
        $this->jsonApi()
            ->get(route('v1.json.course-work-marks.auditLogs', [
                'filter' => ['academicCalendarClass' => $context['academicCalendarClass']->id],
            ]))
            ->assertSuccessful()
            ->assertJsonPath('meta.logs', fn ($logs) => is_array($logs));
    });

    test('unassigned lecturer cannot store marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        [$otherLecturer] = createLecturerUserWithStaff($context);

        jsonApiStoreCourseWorkMark($otherLecturer, $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 70,
        ])->assertForbidden();
    });

    test('academic admin can store without lecturer assignment', function () {
        $context = createCourseWorkJsonApiContext();
        grantCourseWorkLifecyclePermissions($context['user'], [
            'viewAny:academic-calendars',
            'create:course-work',
        ]);
        prepareLecturerCalendar($context);

        jsonApiStoreCourseWorkMark($context['user'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 61,
        ])->assertCreated();
    });

    test('disabled coursework capture rejects store', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);
        $context['departmentCourse']->update(['coursework_capture_enabled' => false]);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 50,
        ])->assertStatus(422);
    });

    test('class and classConfig filters together conflict', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        grantCourseWorkLifecyclePermissions($context['admin'], ['viewAny:course-work']);
        Sanctum::actingAs($context['admin']);

        $this->jsonApi()
            ->get(route('v1.json.course-work-marks.tree', [
                'filter' => [
                    'academicCalendarClass' => $context['academicCalendarClass']->id,
                    'classConfig' => $context['classConfig']->id,
                ],
            ]))
            ->assertStatus(422);
    });

    test('negative marks are rejected', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => -1,
        ])->assertStatus(422);
    });
});

describe('import corners', function () {
    test('import is rejected when capture is disabled', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $context['assessmentType']->update(['weight_percent' => 20]);
        $context['departmentCourse']->update(['coursework_capture_enabled' => false]);

        $data = app(CourseWorkImportTemplateService::class)->assembleForClassConfig(
            (int) $context['classConfig']->id,
            (int) $context['module']->id,
        );
        setCourseWorkWideImportMark($data, (int) $context['assessmentType']->id, 70);
        $file = storeCourseWorkImportFile($data);

        $preview = $this->actingAs($context['admin'])
            ->post(route(
                'academic-calendars.department-classes.course-work-import.preview',
                courseWorkImportRouteParams($context),
            ), [
                'module' => $context['module']->id,
                'file' => $file,
            ]);

        $preview->assertSuccessful();
        expect($preview->json('summary.failed'))->toBeGreaterThan(0);

        $token = $preview->json('previewToken');
        $this->actingAs($context['admin'])
            ->post(route(
                'academic-calendars.department-classes.course-work-import.process',
                courseWorkImportRouteParams($context),
            ), [
                'module' => $context['module']->id,
                'preview_token' => $token,
            ]);

        expect(CourseWorkMark::query()
            ->where('assessment_type_id', $context['assessmentType']->id)
            ->where('mark', 70)
            ->exists())->toBeFalse();
    });

    test('import after calendar lock fails for locked assessment type', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendarId = prepareLecturerCalendar($context);
        $context['assessmentType']->update(['weight_percent' => 20]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subWeeks(3)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $data = app(CourseWorkImportTemplateService::class)->assembleForClassConfig(
            (int) $context['classConfig']->id,
            (int) $context['module']->id,
        );
        setCourseWorkWideImportMark($data, (int) $context['assessmentType']->id, 88);
        $file = storeCourseWorkImportFile($data);

        $routeParams = [
            ...courseWorkImportRouteParams($context),
            'academic_calendar_id' => $calendarId,
        ];

        $preview = $this->actingAs($context['admin'])
            ->post(route(
                'academic-calendars.department-classes.course-work-import.preview',
                $routeParams,
            ), [
                'module' => $context['module']->id,
                'file' => $file,
            ]);

        $preview->assertSuccessful();
        expect($preview->json('summary.failed'))->toBeGreaterThan(0);

        $this->actingAs($context['admin'])
            ->post(route(
                'academic-calendars.department-classes.course-work-import.process',
                $routeParams,
            ), [
                'module' => $context['module']->id,
                'preview_token' => $preview->json('previewToken'),
            ]);

        expect(CourseWorkMark::query()
            ->where('assessment_type_id', $context['assessmentType']->id)
            ->where('mark', 88)
            ->exists())->toBeFalse();
    });

    test('lecturer teaching import page works for assigned module', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $this->actingAs($context['lecturerUser'])
            ->get(route('teaching.classes.import', [
                'academic_calendar_class' => $context['academicCalendarClass']->id,
                'course_syllabus_module' => $context['module']->id,
            ]))
            ->assertSuccessful();
    });

    test('mark-only template uses mark_only layout', function () {
        $context = createCourseWorkJsonApiContext();
        $context['module']->update(['capture_mark_only' => true]);

        $data = app(CourseWorkImportTemplateService::class)->assembleForClassConfig(
            (int) $context['classConfig']->id,
            (int) $context['module']->id,
        );

        expect($data['layout'])->toBe('mark_only');
    });

    test('expired preview token cannot be processed', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $context['assessmentType']->update(['weight_percent' => 20]);

        $data = app(CourseWorkImportTemplateService::class)->assembleForClassConfig(
            (int) $context['classConfig']->id,
            (int) $context['module']->id,
        );
        setCourseWorkWideImportMark($data, (int) $context['assessmentType']->id, 75);
        $file = storeCourseWorkImportFile($data);

        $this->actingAs($context['admin']);
        $preview = $this->post(route(
            'academic-calendars.department-classes.course-work-import.preview',
            courseWorkImportRouteParams($context),
        ), [
            'module' => $context['module']->id,
            'file' => $file,
        ]);
        $preview->assertSuccessful();
        $token = $preview->json('previewToken');

        Cache::forget('course-work-import-preview:'.$token);

        $this->post(route(
            'academic-calendars.department-classes.course-work-import.process',
            courseWorkImportRouteParams($context),
        ), [
            'module' => $context['module']->id,
            'preview_token' => $token,
        ])->assertSessionHasErrors('preview_token');
    });
});

describe('aggregation and grade bands', function () {
    test('course work grade band classifies totals out of 60', function () {
        expect(CourseWorkGradeBand::classify(null))->toBeNull()
            ->and(CourseWorkGradeBand::classify(45))->toBe(CourseWorkGradeBand::DISTINCTION)
            ->and(CourseWorkGradeBand::classify(36))->toBe(CourseWorkGradeBand::MERIT)
            ->and(CourseWorkGradeBand::classify(30))->toBe(CourseWorkGradeBand::PASS)
            ->and(CourseWorkGradeBand::classify(29))->toBe(CourseWorkGradeBand::FAIL)
            ->and(CourseWorkGradeBand::isPassing(CourseWorkGradeBand::PASS))->toBeTrue()
            ->and(CourseWorkGradeBand::isPassing(CourseWorkGradeBand::FAIL))->toBeFalse();
    });

    test('aggregation caps total at 60 when weighted sum exceeds', function () {
        $service = new CourseWorkAggregationService;
        $result = $service->aggregateStudentModule(
            [
                ['id' => 1, 'name' => 'A', 'weightPercent' => 80],
                ['id' => 2, 'name' => 'B', 'weightPercent' => 80],
            ],
            [
                ['assessmentTypeId' => 1, 'mark' => 100, 'remark' => null],
                ['assessmentTypeId' => 2, 'mark' => 100, 'remark' => null],
            ],
        );

        expect($result['isComplete'])->toBeTrue()
            ->and($result['courseWorkTotal60'])->toBe(60);
    });

    test('department and teaching marksheet pdf export', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);
        $context['assessmentType']->update(['weight_percent' => 20]);

        CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 70,
            'created_by' => $context['admin']->id,
            'updated_by' => $context['admin']->id,
        ]);

        $departmentPdf = $this->actingAs($context['admin'])
            ->get(route('academic-calendars.department-classes.course-work-marksheet.export', [
                ...courseWorkMarksheetRouteParams($context),
                'module' => $context['module']->id,
                'format' => 'pdf',
            ]));
        $departmentPdf->assertSuccessful();
        expect($departmentPdf->headers->get('content-type'))->toContain('pdf');

        $teachingPdf = $this->actingAs($context['lecturerUser'])
            ->get(route('teaching.classes.marksheet.export', [
                'academic_calendar_class' => $context['academicCalendarClass']->id,
                'course_syllabus_module' => $context['module']->id,
                'format' => 'pdf',
            ]));
        $teachingPdf->assertSuccessful();
        expect($teachingPdf->headers->get('content-type'))->toContain('pdf');
    });

    test('complete marks aggregate out of 60 and map to a grade band', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $context['assessmentType']->update(['weight_percent' => 100]);

        CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 80,
        ]);

        Sanctum::actingAs($context['admin']);
        $tree = $this->jsonApi()
            ->get(route('v1.json.course-work-marks.tree', [
                'filter' => [
                    'academicCalendarClass' => $context['academicCalendarClass']->id,
                    'studentEnrolment' => $context['studentEnrolment']->id,
                ],
            ]));

        $tree->assertSuccessful();
        $aggregation = $tree->json('meta.syllabi.0.modules.0.aggregation');

        expect($aggregation['isComplete'])->toBeTrue()
            ->and($aggregation['courseWorkTotal60'])->toBe(60)
            ->and(CourseWorkGradeBand::classify($aggregation['courseWorkTotal60']))
            ->toBe(CourseWorkGradeBand::DISTINCTION);
    });
});

describe('missing marks corners', function () {
    test('escalate fails when there are no missing marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => 55,
        ]);

        $this->actingAs($context['vp'])
            ->from(route('missing-marks-report.index'))
            ->post(route('missing-marks-report.escalate'), [
                'assessment_calendar_id' => $calendar->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    });

    test('lecturer cannot view escalate remind or export missing marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($context['lecturerUser'])
            ->get(route('missing-marks-report.index'))
            ->assertForbidden();

        $this->actingAs($context['lecturerUser'])
            ->post(route('missing-marks-report.escalate'), [
                'assessment_calendar_id' => $calendar->id,
            ])
            ->assertForbidden();

        $this->actingAs($context['lecturerUser'])
            ->post(route('missing-marks-report.remind'), [
                'assessment_calendar_id' => $calendar->id,
            ])
            ->assertForbidden();

        $this->actingAs($context['lecturerUser'])
            ->get(route('missing-marks-report.export'))
            ->assertForbidden();
    });

    test('missing marks excel export downloads for vp', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($context['vp'])
            ->get(route('missing-marks-report.export', [
                'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
                'assessment_type_id' => $context['assessmentType']->id,
            ]));

        $response->assertSuccessful();
        expect($response->headers->get('content-type'))->toContain('spreadsheet');
    });

    test('department scoped user only sees own department missing marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        Permission::findOrCreate('viewOnlyOwnDepartment:departments', 'web');
        $context['vp']->givePermissionTo('viewOnlyOwnDepartment:departments');

        /** @var Staff $vpStaff */
        $vpStaff = Staff::query()->create([
            'tenant_id' => $context['tenant']->id,
            'user_id' => $context['vp']->id,
            'title_id' => $context['staff']->title_id,
            'gender_id' => $context['staff']->gender_id,
            'marital_status_id' => $context['staff']->marital_status_id,
            'employment_type_id' => $context['staff']->employment_type_id,
            'employee_number' => 'VP-SCOPE-'.uniqid(),
        ]);
        $vpStaff->institutionDepartments()->attach($context['institutionDepartment']->id);

        $this->actingAs($context['vp']);
        $rows = app(MissingMarksQueryService::class)->forCalendarForCurrentUser($calendar);
        expect($rows)->toHaveCount(1);

        $otherDepartment = $context['institutionDepartment']->replicate();
        $otherDepartment->department_code = 'OTHER-'.uniqid();
        $otherDepartment->save();
        $vpStaff->institutionDepartments()->sync([$otherDepartment->id]);

        $rowsOutside = app(MissingMarksQueryService::class)->forCalendarForCurrentUser($calendar);
        expect($rowsOutside)->toBe([]);
    });

    test('command sends nothing when there are no recipients', function () {
        $context = createCourseWorkJsonApiContext();
        prepareLecturerCalendar($context);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->startOfDay()->addDays(10)->toDateString(),
            'first_notification_days_before' => 10,
            'second_notification_days_before' => 5,
            'due_notification_days_before' => 0,
        ]);

        $calendar = AssessmentCalendar::query()
            ->where('assessment_type_id', $context['assessmentType']->id)
            ->firstOrFail();

        travelToNotificationTier($calendar, MissingMarksNotificationTierEnum::First);
        $this->artisan('assessment-calendars:send-missing-marks-notifications')->assertSuccessful();

        Notification::assertNothingSent();
        expect(AssessmentCalendarNotificationDispatch::query()
            ->where('assessment_calendar_id', $calendar->id)
            ->exists())->toBeFalse();
    });

    test('null mark row appears on missing marks report', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        prepareLecturerCalendar($context);

        CourseWorkMark::query()->create([
            'tenant_id' => $context['tenant']->id,
            'student_enrolment_id' => $context['studentEnrolment']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'mark' => null,
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($context['vp'])
            ->get(route('missing-marks-report.index', [
                'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
                'assessment_type_id' => $context['assessmentType']->id,
            ]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page->has('rows', 1));
    });

    test('ojet enrolments are excluded from missing marks', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $context['modeOfStudy']->update(['name' => ModeOfStudyEnum::OJET->value]);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        expect(app(MissingMarksQueryService::class)->forCalendar($calendar))->toBe([]);
    });

    test('class tutor is used when no module lecturer is assigned', function () {
        $context = createCourseWorkJsonApiContext();
        seedDashboardTestRoles();
        [$lecturerUser, $staff] = createLecturerUserWithStaff($context);
        assignClassTutorOnly($context, $staff);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $context['studentEnrolment']->academic_calendar_id,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        $rows = app(MissingMarksQueryService::class)->forCalendar($calendar);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['lecturerUserIds'])->toContain($lecturerUser->id);
    });
});

describe('windows and locks', function () {
    test('window severity and open state follow notification days', function () {
        $today = Carbon::parse('2026-06-01')->startOfDay();
        Carbon::setTestNow($today);

        $context = createCourseWorkJsonApiContext();
        $calendarId = prepareLecturerCalendar($context);
        AcademicCalendar::query()->whereKey($calendarId)->update([
            'opening_date' => $today->copy()->subMonths(2)->toDateString(),
            'closing_date' => $today->copy()->addMonths(4)->toDateString(),
        ]);

        $calendar = AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => $today->copy()->subDays(20)->toDateString(),
            'end_date' => $today->copy()->addDays(10)->toDateString(),
            'first_notification_days_before' => 10,
            'second_notification_days_before' => 5,
            'due_notification_days_before' => 0,
        ]);

        $service = app(AssessmentCalendarWindowService::class);

        expect($service->severityForDaysRemaining(8, $calendar))->toBe('info')
            ->and($service->severityForDaysRemaining(4, $calendar))->toBe('warning')
            ->and($service->severityForDaysRemaining(0, $calendar))->toBe('critical');

        $windows = $service->windowsForAcademicCalendar($calendarId, [$context['modeOfStudy']->id]);
        expect($windows[0]['isOpen'])->toBeTrue();

        Carbon::setTestNow($today->copy()->subDays(25));
        $beforeStart = $service->windowsForAcademicCalendar($calendarId, [$context['modeOfStudy']->id]);
        expect($beforeStart[0]['isOpen'])->toBeFalse();

        Carbon::setTestNow($today->copy()->addDays(40));
        $afterEnd = $service->windowsForAcademicCalendar($calendarId, [$context['modeOfStudy']->id]);
        expect($afterEnd[0]['isOpen'])->toBeFalse();
    });

    test('only expired assessment type is locked when another remains open', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendarId = prepareLecturerCalendar($context);

        $openType = AssessmentType::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'name' => 'Still Open '.uniqid(),
            'modes_of_study' => [$context['modeOfStudy']->id],
            'weight_percent' => 20,
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subWeeks(3)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $openType->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 40,
        ], ['academic_calendar_id' => $calendarId])->assertStatus(422);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $openType->id,
            'mark' => 70,
        ], ['academic_calendar_id' => $calendarId])->assertCreated();
    });

    test('latest end date wins when same type has multiple calendars', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendarId = prepareLecturerCalendar($context);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->subWeeks(2)->toDateString(),
        ]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
        ]);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 63,
        ], ['academic_calendar_id' => $calendarId])->assertCreated();
    });

    test('mark-only modules remain editable after assessment calendars lock', function () {
        $context = createCourseWorkLifecycleActors(createCourseWorkJsonApiContext());
        $calendarId = prepareLecturerCalendar($context);
        $context['module']->update(['capture_mark_only' => true]);

        AssessmentCalendar::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'assessment_type_id' => $context['assessmentType']->id,
            'academic_calendar_id' => $calendarId,
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'start_date' => now()->subWeeks(2)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        jsonApiStoreCourseWorkMark($context['lecturerUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'mark' => 77,
        ], ['academic_calendar_id' => $calendarId])->assertCreated();
    });
});

describe('rbac corners', function () {
    test('guests are redirected from marksheet import and missing marks', function () {
        $context = createCourseWorkJsonApiContext();

        $this->get(route('academic-calendars.department-classes.course-work-marksheet', courseWorkMarksheetRouteParams($context)))
            ->assertRedirect('/login');

        $this->get(route('academic-calendars.department-classes.course-work-import', courseWorkImportRouteParams($context)))
            ->assertRedirect('/login');

        $this->get(route('missing-marks-report.index'))
            ->assertRedirect('/login');
    });

    test('student cannot store course work marks via json api', function () {
        $context = createCourseWorkJsonApiContext();
        prepareLecturerCalendar($context);

        jsonApiStoreCourseWorkMark($context['studentUser'], $context, [
            'studentEnrolmentId' => $context['studentEnrolment']->id,
            'courseSyllabusModuleId' => $context['module']->id,
            'assessmentTypeId' => $context['assessmentType']->id,
            'mark' => 90,
        ])->assertForbidden();
    });
});
