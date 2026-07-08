<?php

namespace App\JsonApi\V1\HMS\HostelRooms;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelRooms\Filters\HostelRoomAvailableForApplicationFilter;
use App\JsonApi\V1\HMS\HostelRooms\Filters\HostelRoomHostelFilter;
use App\JsonApi\V1\HMS\HostelRooms\Filters\HostelRoomSearchFilter;
use App\Models\HMS\HostelRoom;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class HostelRoomSchema extends Schema
{
    public static string $model = HostelRoom::class;

    protected ?string $uriType = 'hms/hostel-rooms';

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
            Number::make('sectionCount')->extractUsing(
                fn (HostelRoom $room) => (int) ($room->sections_count ?? 0)
            )->readOnly(),
            Number::make('occupiedSectionCount')->extractUsing(
                fn (HostelRoom $room) => min(
                    (int) ($room->occupied_sections_count ?? 0),
                    (int) ($room->sections_count ?? 0),
                )
            )->readOnly(),
            Number::make('availableSectionCount')->extractUsing(
                fn (HostelRoom $room) => max(
                    0,
                    (int) ($room->sections_count ?? 0) - min((int) ($room->occupied_sections_count ?? 0), (int) ($room->sections_count ?? 0)),
                )
            )->readOnly(),
            Number::make('roomAmenitiesCount')->extractUsing(
                fn (HostelRoom $room) => (int) ($room->room_amenities_count ?? 0)
            )->readOnly(),
            Number::make('sectionAmenitiesCount')->extractUsing(
                fn (HostelRoom $room) => (int) ($room->section_amenities_count ?? 0)
            )->readOnly(),
            Number::make('totalAmenitiesCount')->extractUsing(
                fn (HostelRoom $room) => (int) (($room->room_amenities_count ?? 0) + ($room->section_amenities_count ?? 0))
            )->readOnly(),
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
            app(HostelRoomAvailableForApplicationFilter::class),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }

    public function newQuery($query = null): JsonApiBuilder
    {
        $query ??= HostelRoom::query();

        $query
            ->withCount('sections')
            ->withCount('amenities as room_amenities_count')
            ->selectSub(
                DB::table('hostel_room_allocations')
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_room_allocations.hostel_room_id', 'hostel_rooms.id')
                    ->where('hostel_room_allocations.status', 'active')
                    ->limit(1),
                'occupied_sections_count',
            )
            ->selectSub(
                DB::table('amenityables')
                    ->join('hostel_room_sections', function ($join) {
                        $join->on('amenityables.amenityable_id', '=', 'hostel_room_sections.id')
                            ->where('amenityables.amenityable_type', HostelRoomSection::class);
                    })
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_room_sections.hostel_room_id', 'hostel_rooms.id')
                    ->limit(1),
                'section_amenities_count',
            );

        return parent::newQuery($query);
    }
}
