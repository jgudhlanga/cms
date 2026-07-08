<?php

namespace App\JsonApi\V1\HMS\Hostels;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\Hostels\Filters\HostelSearchFilter;
use App\JsonApi\V1\HMS\Hostels\Filters\HostelTypeFilter;
use App\JsonApi\V1\HMS\Hostels\Filters\HostelWardenFilter;
use App\Models\HMS\Hostel;
use App\Models\HMS\HostelRoomSection;
use Illuminate\Support\Facades\DB;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class HostelSchema extends Schema
{
    public static string $model = Hostel::class;

    protected ?string $uriType = 'hms/hostels';

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
            Number::make('occupiedCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->occupied_beds_sum ?? 0),
            )->readOnly(),
            Number::make('vacantCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->vacant_rooms_count ?? 0),
            )->readOnly(),
            Number::make('maintenanceCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->maintenance_rooms_count ?? 0),
            )->readOnly(),
            Number::make('sectionCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->sections_count ?? 0),
            )->readOnly(),
            Number::make('occupiedSectionCount')->extractUsing(
                fn (Hostel $hostel) => min(
                    (int) ($hostel->occupied_sections_count ?? 0),
                    (int) ($hostel->sections_count ?? 0),
                ),
            )->readOnly(),
            Number::make('availableSectionCount')->extractUsing(
                fn (Hostel $hostel) => max(
                    0,
                    (int) ($hostel->sections_count ?? 0) - min((int) ($hostel->occupied_sections_count ?? 0), (int) ($hostel->sections_count ?? 0)),
                ),
            )->readOnly(),
            Number::make('roomAmenitiesCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->room_amenities_count ?? 0),
            )->readOnly(),
            Number::make('sectionAmenitiesCount')->extractUsing(
                fn (Hostel $hostel) => (int) ($hostel->section_amenities_count ?? 0),
            )->readOnly(),
            Number::make('totalAmenitiesCount')->extractUsing(
                fn (Hostel $hostel) => (int) (($hostel->room_amenities_count ?? 0) + ($hostel->section_amenities_count ?? 0)),
            )->readOnly(),
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
            new HostelTypeFilter,
            new HostelWardenFilter,
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
        $query ??= Hostel::query();

        $query
            ->withSum('rooms as occupied_beds_sum', 'current_occupancy')
            ->withCount([
                'rooms as vacant_rooms_count' => fn ($builder) => $builder->where('status', 'vacant'),
                'rooms as maintenance_rooms_count' => fn ($builder) => $builder->where('status', 'maintenance'),
            ]);

        $query
            ->selectSub(
                DB::table('hostel_room_sections')
                    ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_sections.hostel_room_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_rooms.hostel_id', 'hostels.id')
                    ->limit(1),
                'sections_count',
            )
            ->selectSub(
                DB::table('hostel_room_allocations')
                    ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_allocations.hostel_room_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_rooms.hostel_id', 'hostels.id')
                    ->where('hostel_room_allocations.status', 'active')
                    ->limit(1),
                'occupied_sections_count',
            )
            ->selectSub(
                DB::table('hostel_room_amenity')
                    ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_amenity.hostel_room_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_rooms.hostel_id', 'hostels.id')
                    ->limit(1),
                'room_amenities_count',
            )
            ->selectSub(
                DB::table('amenityables')
                    ->join('hostel_room_sections', function ($join) {
                        $join->on('amenityables.amenityable_id', '=', 'hostel_room_sections.id')
                            ->where('amenityables.amenityable_type', HostelRoomSection::class);
                    })
                    ->join('hostel_rooms', 'hostel_rooms.id', '=', 'hostel_room_sections.hostel_room_id')
                    ->selectRaw('count(*)')
                    ->whereColumn('hostel_rooms.hostel_id', 'hostels.id')
                    ->limit(1),
                'section_amenities_count',
            );

        return parent::newQuery($query);
    }
}
