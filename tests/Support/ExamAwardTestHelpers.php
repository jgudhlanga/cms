<?php

declare(strict_types=1);

use App\Actions\Institution\SyncProgrammeSemestersForOfferingAction;
use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\Semester;
use App\Models\Applications\ApplicationCourseRequirement;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\DepartmentLevelCourse;
use App\Models\Institution\Level;
use App\Models\Institution\Syllabus\CourseSyllabus;
use App\Models\Institution\Syllabus\CourseSyllabusModule;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Models\Students\StudentEnrolmentStatus;
use Illuminate\Support\Str;

require_once __DIR__.'/BulkFinaliseTestHelpers.php';

if (! function_exists('seedExamAwardLookups')) {
    function seedExamAwardLookups(): void
    {
        foreach (['Active', 'Award', 'Proceed', 'Referred', 'Unknown'] as $name) {
            StudentEnrolmentStatus::query()->firstOrCreate(['name' => $name], ['description' => 'Test']);
        }

        Semester::query()->firstOrCreate(['slug' => 'semester-1'], ['name' => 'Semester 1']);
        Semester::query()->firstOrCreate(['slug' => 'semester-2'], ['name' => 'Semester 2']);
    }
}

if (! function_exists('seedTaughtSyllabusForOffering')) {
    /**
     * One module per taught programme semester, each also carrying the calendar semester_id that
     * matches its position — so a phase resolved by calendar half alone would pick the wrong one.
     */
    function seedTaughtSyllabusForOffering(
        DepartmentLevelCourse $offering,
        StudentEnrolment $enrolment,
    ): CourseSyllabus {
        $syllabus = CourseSyllabus::query()->create([
            'tenant_id' => $enrolment->student->tenant_id,
            'institution_department_id' => $enrolment->institution_department_id,
            'department_level_course_id' => $offering->id,
            'title' => 'ND Biomedical Engineering',
            'code' => 'SYL-'.Str::upper(Str::random(6)),
            'implementation_year' => '2026',
        ]);

        $taught = $offering->programmeSemesters->sortBy('position')->values();

        foreach ([0 => 'Y1S1-A', 1 => 'Y1S2-A'] as $index => $code) {
            $programmeSemester = $taught->get($index);

            if ($programmeSemester === null) {
                continue;
            }

            CourseSyllabusModule::query()->create([
                'tenant_id' => $syllabus->tenant_id,
                'course_syllabus_id' => $syllabus->id,
                'programme_semester_id' => $programmeSemester->id,
                'semester_id' => (int) Semester::query()->where('slug', 'semester-'.($index + 1))->value('id'),
                'title' => $code.' module',
                'code' => $code,
                'all_semesters' => false,
                'capture_mark_only' => true,
            ]);
        }

        return $syllabus;
    }
}

if (! function_exists('createAugustIntakeNdContext')) {
    /**
     * A student who starts ND in the year's second calendar period, on a course that also offers
     * NC and requires it — the shape of student 23746.
     *
     * @return array{
     *     student: Student,
     *     enrolment: StudentEnrolment,
     *     nd: DepartmentLevelCourse,
     *     ncDepartmentLevel: DepartmentLevel
     * }
     */
    function createAugustIntakeNdContext(): array
    {
        $application = createVerifiedStudentApplication('AWARD-'.Str::upper(Str::random(4)));
        $tenantId = (int) $application->tenant_id;

        $ncLevel = Level::factory()->create([
            'name' => 'NC',
            'position' => 1,
            'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
        ]);
        $application->departmentLevel->level->update([
            'name' => 'ND',
            'position' => 2,
            'calendar_type' => AcademicCalendarTypeEnum::SEMESTER,
        ]);

        $ncDepartmentLevel = DepartmentLevel::query()->create([
            'tenant_id' => $tenantId,
            'institution_department_id' => $application->institution_department_id,
            'level_id' => $ncLevel->id,
        ]);

        $nc = DepartmentLevelCourse::query()->create([
            'department_course_id' => $application->department_course_id,
            'department_level_id' => $ncDepartmentLevel->id,
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => false,
            'attachment_semester_count' => 0,
        ]);

        $nd = DepartmentLevelCourse::query()
            ->where('department_course_id', $application->department_course_id)
            ->where('department_level_id', $application->department_level_id)
            ->firstOrFail();

        $nd->update([
            'duration_years' => 1,
            'taught_semester_count' => 2,
            'includes_industrial_attachment' => true,
            'attachment_semester_count' => 2,
        ]);

        $sync = app(SyncProgrammeSemestersForOfferingAction::class);
        $sync->execute($nc);
        $sync->execute($nd->fresh() ?? $nd);

        ApplicationCourseRequirement::query()->create([
            'tenant_id' => $tenantId,
            'department_level_id' => $nd->department_level_id,
            'department_course_id' => $application->department_course_id,
            'is_o_level_required' => false,
            'required_subjects_count' => 0,
            'main_subjects_count' => 0,
            'main_subject_ids' => [],
            'other_subjects_count' => 0,
            'only_read_write_required' => false,
            'required_level_id' => $ncLevel->id,
        ]);

        // Two calendar periods, as a real year has. The student starts in the second.
        AcademicCalendar::query()->firstOrCreate(
            ['calendar_year' => '2026', 'type' => 'semester', 'opening_date' => '2026-02-03'],
            ['closing_date' => '2026-06-05'],
        );
        $augustCalendar = AcademicCalendar::query()->firstOrCreate(
            ['calendar_year' => '2026', 'type' => 'semester', 'opening_date' => '2026-08-17'],
            ['closing_date' => '2026-12-04'],
        );

        $enrolment = StudentEnrolment::query()->create([
            'student_id' => $application->student_id,
            'student_application_id' => $application->id,
            'institution_department_id' => $application->institution_department_id,
            'department_level_id' => $application->department_level_id,
            'department_course_id' => $application->department_course_id,
            'semester_id' => (int) Semester::query()->where('slug', 'semester-2')->value('id'),
            'academic_calendar_id' => $augustCalendar->id,
            'mode_of_study_id' => $application->mode_of_study_id,
            'student_enrolment_status_id' => (int) StudentEnrolmentStatus::query()->where('slug', 'active')->value('id'),
        ]);

        return [
            'student' => $application->student->fresh() ?? $application->student,
            'enrolment' => $enrolment->fresh() ?? $enrolment,
            'nd' => $nd->fresh(['programmeSemesters']) ?? $nd,
            'ncDepartmentLevel' => $ncDepartmentLevel,
        ];
    }
}
