<?php

declare(strict_types=1);

namespace App\Queries\Enrolments;

use App\Enums\Shared\ClassListTypeEnum;
use App\Models\Students\StudentEnrolment;
use Illuminate\Database\Eloquent\Builder;

class StudentEnrollmentExportQuery
{
    public function baseQuery(?string $intakeYear = null): Builder
    {
        return StudentEnrolment::query()
            ->whereHas('studentProgram', function (Builder $query) use ($intakeYear): void {
                $query
                    ->whereNull('student_programs.deleted_at')
                    ->whereHas('classList', function (Builder $classListQuery): void {
                        $classListQuery
                            ->where('type', ClassListTypeEnum::FINAL->value)
                            ->whereNull('class_lists.deleted_at');
                    })
                    ->when($intakeYear !== null, function (Builder $query) use ($intakeYear): void {
                        $query->whereHas('intakePeriod', fn (Builder $intakeQuery) => $intakeQuery
                            ->where('calendar_year', $intakeYear)
                            ->whereNull('intake_periods.deleted_at'));
                    });
            })
            ->whereNull('student_enrolments.deleted_at')
            ->with([
                'student.user',
                'student.gender',
                'student.addresses',
                'student.nextOfKins.relationship',
                'student.nextOfKins.contacts',
                'student.nextOfKins.addresses',
                'student.sponsors',
                'studentProgram.intakePeriod',
                'studentProgram.modeOfStudy',
                'departmentCourse',
                'academicYearOption',
                'academicCalendar',
                'academicCalendarStudentEnrolment.academicCalendarClass.classConfig',
            ])
            ->orderBy('student_enrolments.id');
    }
}
