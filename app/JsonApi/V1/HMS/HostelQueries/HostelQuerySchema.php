<?php

namespace App\JsonApi\V1\HMS\HostelQueries;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelQueries\Filters\QueryStudentFilter;
use App\Models\HMS\HostelQuery;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelQuerySchema extends Schema
{
    public static string $model = HostelQuery::class;

    protected ?string $uriType = 'hms/hostel-queries';

    protected array $with = ['student.user'];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('studentId', 'student_id'),
            Str::make('category')->extractUsing(fn (HostelQuery $query) => $query->category?->value),
            Str::make('categoryLabel')->extractUsing(fn (HostelQuery $query) => $query->category?->label())->readOnly(),
            Str::make('subject'),
            Str::make('description'),
            Str::make('priority')->extractUsing(fn (HostelQuery $query) => $query->priority?->value),
            Str::make('priorityLabel')->extractUsing(fn (HostelQuery $query) => $query->priority?->label())->readOnly(),
            Str::make('status')->extractUsing(fn (HostelQuery $query) => $query->status?->value)->sortable(),
            Str::make('statusLabel')->extractUsing(fn (HostelQuery $query) => $query->status?->label())->readOnly(),
            Str::make('resolutionNotes', 'resolution_notes'),
            Str::make('studentName')->extractUsing(
                fn (HostelQuery $query) => $query->student?->user?->full_name
            )->readOnly(),
            Str::make('studentNumber')->extractUsing(
                fn (HostelQuery $query) => $query->student?->student_number
            )->readOnly(),
            DateTime::make('resolvedAt', 'resolved_at')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new QueryStudentFilter,
            Where::make('status'),
            Where::make('priority'),
            Where::make('category'),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
