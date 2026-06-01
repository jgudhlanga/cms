<?php

namespace App\JsonApi\V1\HMS\HostelNotices;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelNotices\Filters\NoticeStudentFilter;
use App\Models\HMS\HostelNotice;
use App\Services\HMS\HostelNoticeAudienceService;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayHash;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelNoticeSchema extends Schema
{
    public static string $model = HostelNotice::class;

    protected ?string $uriType = 'hms/hostel-notices';

    protected array $with = ['postedByUser', 'hostels', 'noticeFloors', 'students.user'];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('title'),
            Str::make('content'),
            Str::make('noticeType', 'type')->extractUsing(fn (HostelNotice $notice) => $notice->type?->value),
            Str::make('noticeTypeLabel')->extractUsing(fn (HostelNotice $notice) => $notice->type?->label())->readOnly(),
            Str::make('status')->extractUsing(fn (HostelNotice $notice) => $notice->status?->value)->sortable(),
            Str::make('statusLabel')->extractUsing(fn (HostelNotice $notice) => $notice->status?->label())->readOnly(),
            Boolean::make('isUrgent', 'is_urgent'),
            Str::make('postedByName')->extractUsing(
                fn (HostelNotice $notice) => $notice->postedByUser?->full_name
            )->readOnly(),
            Number::make('postedBy', 'posted_by')->readOnly(),
            DateTime::make('publishedAt', 'published_at'),
            DateTime::make('expiresAt', 'expires_at'),
            ArrayList::make('audienceHostelIds')->extractUsing(
                fn (HostelNotice $notice) => $notice->hostels->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            ),
            ArrayList::make('audienceFloors')->extractUsing(
                fn (HostelNotice $notice) => $notice->noticeFloors
                    ->map(fn ($floor) => [
                        'hostelId' => (int) $floor->hostel_id,
                        'floorNumber' => (int) $floor->floor_number,
                    ])
                    ->values()
                    ->all()
            ),
            ArrayList::make('audienceStudentIds')->extractUsing(
                fn (HostelNotice $notice) => $notice->students->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            ),
            ArrayHash::make('audience')->extractUsing(
                fn (HostelNotice $notice) => app(HostelNoticeAudienceService::class)->audiencePayload($notice)
            )->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new NoticeStudentFilter,
            Where::make('status'),
            Where::make('type', 'type'),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
