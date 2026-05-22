<?php

namespace App\JsonApi\V1\HostelRooms;

use App\JsonApi\V1\Filters\HostelRoomHostelFilter;
use App\JsonApi\V1\Filters\HostelRoomSearchFilter;
use App\JsonApi\V1\Filters\TrashedFilter;
use App\Models\HMS\HostelRoom;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelRoomSchema extends Schema
{
    public static string $model = HostelRoom::class;

    protected array $with = ['hostel:id,name'];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    protected $defaultSort = 'name';

    public function fields(): array
    {
        return [
            ID::make(),
            Number::make('hostelId', 'hostel_id'),
            Str::make('hostelName')->extractUsing(
                fn (HostelRoom $room) => $room->hostel?->name
            )->readOnly(),
            Str::make('name')->sortable(),
            Str::make('roomType', 'room_type')->sortable(),
            Number::make('capacity'),
            Str::make('status')->sortable(),
            Number::make('maxOccupancy', 'max_occupancy'),
            Str::make('occupancy')->extractUsing(
                fn (HostelRoom $room) => $room->occupancyLabel()
            )->readOnly(),
            Number::make('floorNumber', 'floor_number')->sortable(),
            Str::make('description'),
            BelongsTo::make('hostel')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new HostelRoomSearchFilter,
            new HostelRoomHostelFilter,
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
