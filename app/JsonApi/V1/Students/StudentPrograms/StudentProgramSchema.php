<?php

namespace App\JsonApi\V1\Students\StudentPrograms;

use App\JsonApi\V1\Students\StudentPrograms\Filters\StudentFilter;
use App\Models\Students\StudentProgram;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class StudentProgramSchema extends Schema
{
    public static string $model = StudentProgram::class;

    protected ?string $uriType = 'students/student-programs';

    protected array $with = [
        'institutionDepartment.department',
        'departmentLevel.level',
        'departmentCourse.course',
        'departmentWorkflowStep.workflowStep',
        'intakePeriod',
    ];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 50];

    protected $defaultSort = '-createdAt';

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('studentId', 'student_id')->readOnly(),
            Str::make('department')->extractUsing(
                fn (StudentProgram $program) => $program->institutionDepartment?->department?->name
            )->readOnly(),
            Str::make('level')->extractUsing(
                fn (StudentProgram $program) => $program->departmentLevel?->level?->name
            )->readOnly(),
            Str::make('course')->extractUsing(
                fn (StudentProgram $program) => $program->departmentCourse?->course?->name
            )->readOnly(),
            Number::make('intakePeriodId', 'intake_period_id')->readOnly(),
            Str::make('intakePeriod')->extractUsing(
                fn (StudentProgram $program) => $program->intakePeriod?->name
            )->readOnly(),
            Str::make('intakePeriodCalendarYear')->extractUsing(
                fn (StudentProgram $program) => $program->intakePeriod?->calendar_year
            )->readOnly(),
            DateTime::make('intakePeriodStartDate')->extractUsing(
                fn (StudentProgram $program) => $program->intakePeriod?->start_date
            )->readOnly(),
            Str::make('applicationTrackingNumber', 'application_tracking_number')->readOnly(),
            Str::make('workflowStep')->extractUsing(
                fn (StudentProgram $program) => $program->departmentWorkflowStep?->workflowStep?->name
            )->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new StudentFilter,
        ];
    }

    public function newQuery($query = null): JsonApiBuilder
    {
        $builder = parent::newQuery($query);
        $eloquent = $builder->getQuery();

        if (request()->query('sort') === null) {
            $eloquent->latest('student_programs.created_at');
        }

        return $builder;
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage(50);
    }
}
