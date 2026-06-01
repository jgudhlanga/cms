<?php

namespace App\JsonApi\V1\AcademicCalendars\CourseWorkMarks;

use App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters\AcademicCalendarClassFilter;
use App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters\ClassConfigFilter;
use App\JsonApi\V1\AcademicCalendars\CourseWorkMarks\Filters\StudentEnrolmentFilter;
use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\Models\AcademicCalendars\CourseWorkMark;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class CourseWorkMarkSchema extends Schema
{
    public static string $model = CourseWorkMark::class;

    protected ?string $uriType = 'academic-calendars/course-work-marks';

    protected array $with = [
        'studentEnrolment',
        'courseSyllabusModule',
        'assessmentType',
        'createdBy',
        'updatedBy',
    ];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 50];

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('studentEnrolmentId', 'student_enrolment_id'),
            Number::make('courseSyllabusModuleId', 'course_syllabus_module_id'),
            Number::make('assessmentTypeId', 'assessment_type_id'),
            Number::make('mark')->sortable(),
            Str::make('remark'),
            Number::make('createdBy', 'created_by')->readOnly(),
            Number::make('updatedBy', 'updated_by')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this)->delimiter(','),
            AcademicCalendarClassFilter::make(),
            ClassConfigFilter::make(),
            StudentEnrolmentFilter::make(),
            Where::make('courseSyllabusModule', 'course_syllabus_module_id'),
            Where::make('assessmentType', 'assessment_type_id'),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }
}
