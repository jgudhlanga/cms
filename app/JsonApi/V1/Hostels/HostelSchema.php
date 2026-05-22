<?php

namespace App\JsonApi\V1\Hostels;

use App\JsonApi\V1\Filters\HostelSearchFilter;
use App\JsonApi\V1\Filters\HostelWardenFilter;
use App\JsonApi\V1\Filters\TrashedFilter;
use App\Models\HMS\Hostel;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelSchema extends Schema
{
    public static string $model = Hostel::class;

    protected array $with = ['warden.user'];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    protected $defaultSort = 'name';

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('type')->sortable(),
            Number::make('capacity'),
            Number::make('roomsCount', 'rooms_count'),
            Number::make('floorCount', 'floor_count'),
            Str::make('status')->sortable(),
            Str::make('location')->sortable(),
            Str::make('description'),
            Number::make('occupiedCount')->extractUsing(fn () => 0)->readOnly(),
            Number::make('vacantCount')->extractUsing(fn () => 0)->readOnly(),
            Number::make('maintenanceCount')->extractUsing(fn () => 0)->readOnly(),
            Number::make('wardenId', 'warden_id'),
            Str::make('wardenName')->extractUsing(
                fn (Hostel $hostel) => $hostel->warden?->user?->full_name
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
            new HostelSearchFilter,
            Where::make('type'),
            new HostelWardenFilter,
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
