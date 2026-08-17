<?php

declare(strict_types=1);

namespace App\JsonApi\V1\Students\StudentIdCardRequests;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\Models\Students\StudentIdCardRequest;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class StudentIdCardRequestSchema extends Schema
{
    public static string $model = StudentIdCardRequest::class;

    protected ?string $uriType = 'students/student-id-card-requests';

    protected array $with = [
        'student.user',
        'student.latestEnrolment.departmentCourse.course',
        'photo',
        'reviewer',
    ];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    protected $defaultSort = '-createdAt';

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('studentId', 'student_id')->readOnly(),
            Str::make('status')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->status?->value
            )->sortable(),
            Str::make('statusLabel')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->status?->label()
            )->readOnly(),
            Str::make('reason')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->reason?->value
            ),
            Str::make('reasonLabel')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->reason?->label()
            )->readOnly(),
            Str::make('notes'),
            Str::make('rejectionReason', 'rejection_reason')->readOnly(),
            Str::make('serialNumber', 'serial_number')->readOnly(),
            Str::make('photoThumbUrl')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->photoUrl('thumb')
            )->readOnly(),
            Str::make('studentName')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->student?->user?->full_name
            )->readOnly(),
            Str::make('studentNumber')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->student?->student_number
            )->readOnly(),
            Str::make('programme')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->student?->latestEnrolment?->departmentCourse?->course?->name
            )->readOnly(),
            Str::make('reviewedByName')->extractUsing(
                fn (StudentIdCardRequest $request) => $request->reviewer?->full_name
            )->readOnly(),
            DateTime::make('reviewedAt', 'reviewed_at')->readOnly(),
            DateTime::make('printedAt', 'printed_at')->readOnly(),
            DateTime::make('issuedAt', 'issued_at')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('reason'),
            new StudentIdCardRequestSearchFilter,
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
