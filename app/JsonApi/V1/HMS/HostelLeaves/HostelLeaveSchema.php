<?php

namespace App\JsonApi\V1\HMS\HostelLeaves;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelLeaves\Filters\LeaveStudentFilter;
use App\Models\HMS\HostelLeave;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelLeaveSchema extends Schema
{
    public static string $model = HostelLeave::class;

    protected ?string $uriType = 'hms/hostel-leaves';

    protected array $with = ['student.user', 'reviewedByUser'];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('studentId', 'student_id'),
            Str::make('leaveType', 'leave_type'),
            DateTime::make('fromDate', 'from_date'),
            DateTime::make('toDate', 'to_date'),
            Str::make('reason'),
            Str::make('status')->extractUsing(fn (HostelLeave $leave) => $leave->status?->value)->sortable(),
            Str::make('statusLabel')->extractUsing(fn (HostelLeave $leave) => $leave->status?->label())->readOnly(),
            Str::make('reviewNotes', 'review_notes'),
            Str::make('studentName')->extractUsing(
                fn (HostelLeave $leave) => $leave->student?->user?->full_name
            )->readOnly(),
            Str::make('studentNumber')->extractUsing(
                fn (HostelLeave $leave) => $leave->student?->student_number
            )->readOnly(),
            Str::make('reviewedByName')->extractUsing(
                fn (HostelLeave $leave) => $leave->reviewedByUser?->full_name
            )->readOnly(),
            DateTime::make('reviewedAt', 'reviewed_at')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new LeaveStudentFilter,
            Where::make('status'),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
