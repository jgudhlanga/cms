<?php

declare(strict_types=1);

use App\Actions\Students\ContinueAndReseatStudentsAction;
use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Models\AcademicCalendars\Semester;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentSemester;

require_once __DIR__.'/../../Support/AcademicCalendarClassTestHelpers.php';

it('returns run_id as null when no enrolments are processed', function (): void {
    $context = buildDepartmentClassContext();

    $sourceClass = AcademicCalendarClass::query()->create([
        'tenant_id' => $context['tenant']->id,
        'class_config_id' => $context['classConfig']->id,
        'name' => 'LEVEL-1-FULL-TIME-1',
    ]);

    $result = app(ContinueAndReseatStudentsAction::class)->execute(
        collect(),
        $sourceClass,
        $context['user']->id,
    );

    expect($result)->toBe([
        'advanced' => 0,
        'reseated' => 0,
        'run_id' => null,
        'skipped_reasons' => [],
    ]);
});

it('returns the last-semester reason when selected students cannot continue', function (): void {
    $context = buildDepartmentClassContext();
    $context['user']->givePermissionTo('update:academic-calendar-student-enrolments');
    createFinalStudentApplication($context, 'advance-blocked@example.com');

    $this->actingAs($context['user']);

    $this->post(route('academic-calendars.department-classes.store', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
    ]), [
        'class_config_id' => $context['classConfig']->id,
        'department_level_id' => $context['departmentLevel']->id,
        'department_course_id' => $context['departmentCourse']->id,
        'mode_of_study_id' => $context['modeOfStudy']->id,
        'students_per_class' => 2,
    ])->assertSessionHas('success');

    $academicCalendarClass = AcademicCalendarClass::query()->firstOrFail();
    $enrolment = StudentEnrolment::query()->firstOrFail();
    $semesterTwo = Semester::query()->firstOrCreate(
        ['slug' => 'semester-2'],
        ['name' => 'Semester 2', 'description' => null],
    );

    StudentSemester::query()->updateOrCreate(
        [
            'student_enrolment_id' => $enrolment->id,
            'semester_id' => $semesterTwo->id,
        ],
        ['student_enrolment_status_id' => $enrolment->student_enrolment_status_id],
    );
    $enrolment->update(['semester_id' => $semesterTwo->id]);

    $expectedReason = __('students.enrolment_cannot_advance_last_phase', ['phase' => 'Semester 2']);

    $this->post(route('academic-calendars.department-classes.advance-phase', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]), [
        'student_enrolment_ids' => [$enrolment->id],
    ])->assertSessionHasErrors(['student_enrolment_ids' => $expectedReason]);

    $page = $this->get(route('academic-calendars.department-classes.show', [
        'institution_department' => $context['institutionDepartment']->id,
        'calendar_year' => $context['calendar']->calendar_year,
        'academic_calendar_class' => $academicCalendarClass->id,
    ]))->assertSuccessful()->viewData('page');

    $student = collect(data_get($page, 'props.academicCalendarClass.students'))
        ->firstWhere('studentEnrolmentId', $enrolment->id);

    expect($student['canAdvanceToNextPhase'])->toBeFalse()
        ->and($student['cannotAdvancePhaseReason'])->toBe($expectedReason);
});
