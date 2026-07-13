<?php

namespace App\JsonApi\V1\HMS\HostelRoomAllocations;

use App\Enums\HMS\HostelAllocationStatusEnum;
use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationGenderFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationHostelFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationNameFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationRoomFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationSearchFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationStatusFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationStudentFilter;
use App\JsonApi\V1\HMS\HostelRoomAllocations\Filters\AllocationTypeFilter;
use App\Models\HMS\HostelRoomAllocation;
use App\Support\HMS\HmsStudentAccess;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\ArrayList;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class HostelRoomAllocationSchema extends Schema
{
    public static string $model = HostelRoomAllocation::class;

    protected ?string $uriType = 'hms/hostel-room-allocations';

    protected array $with = [
        'student.user',
        'student.gender',
        'student.latestEnrolment.institutionDepartment.department',
        'student.latestEnrolment.departmentLevel.level',
        'student.latestEnrolment.departmentCourse.course',
        'room.hostel',
        'room.amenities',
        'section.amenities',
    ];

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    public function fields(): array
    {
        $fields = [
            ID::make(),
            Str::make('allocationType')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->type?->value
            )->readOnly(),
            Str::make('allocationTypeLabel')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->type?->label()
            )->readOnly(),
            Str::make('status', 'status')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->status?->value
            )->readOnly()->sortable(),
            Str::make('statusLabel')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->status?->label()
            )->readOnly(),
            DateTime::make('checkIn', 'check_in')->readOnly(),
        ];

        if (HmsStudentAccess::canViewCheckoutDates(Auth::user())) {
            $fields[] = DateTime::make('checkOut', 'check_out')->readOnly();
        }

        return array_merge($fields, [
            Number::make('studentId', 'student_id')->readOnly(),
            Str::make('studentNumber')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->student?->student_number
            )->readOnly(),
            Str::make('studentName')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->student?->user?->full_name
            )->readOnly(),
            Str::make('gender')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->student?->gender?->title
            )->readOnly(),
            Str::make('course')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->student?->latestEnrolment?->departmentCourse?->course?->name
            )->readOnly(),
            Str::make('level')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->student?->latestEnrolment?->departmentLevel?->level?->name
            )->readOnly(),
            Number::make('hostelId')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->hostel_id
            )->readOnly(),
            Str::make('hostelName')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->hostel?->name
            )->readOnly(),
            Number::make('roomId', 'hostel_room_id')->readOnly(),
            Number::make('hostelRoomId', 'hostel_room_id'),
            Str::make('roomName')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->name
            )->readOnly(),
            Number::make('floorNumber')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->floor_number
            )->readOnly(),
            Str::make('roomType')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->room_type
            )->readOnly(),
            Str::make('roomStatus')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->status
            )->readOnly(),
            Number::make('maxOccupancy')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->max_occupancy
            )->readOnly(),
            Number::make('currentOccupancy')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->current_occupancy
            )->readOnly(),
            Str::make('occupancyLabel')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->room?->occupancyLabel()
            )->readOnly(),
            Number::make('sectionId', 'hostel_room_section_id')->readOnly(),
            Str::make('sectionName')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $allocation->section?->name
            )->readOnly(),
            ArrayList::make('amenities')->extractUsing(
                fn (HostelRoomAllocation $allocation) => $this->amenitiesForAllocation($allocation)
            )->readOnly(),
            BelongsTo::make('student')->readOnly(),
            BelongsTo::make('room')->readOnly(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ]);
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new AllocationSearchFilter,
            new AllocationNameFilter,
            new AllocationGenderFilter,
            new AllocationHostelFilter,
            new AllocationRoomFilter,
            new AllocationTypeFilter,
            new AllocationStatusFilter,
            new AllocationStudentFilter,
            TrashedFilter::make(),
        ];
    }

    public function newQuery($query = null): JsonApiBuilder
    {
        $builder = parent::newQuery($query);
        $eloquent = $builder->getQuery();
        $filters = request()->input('filter', []);

        if (! array_key_exists('status', $filters) || $filters['status'] === '' || $filters['status'] === null) {
            $eloquent->whereIn(
                'hostel_room_allocations.status',
                HostelAllocationStatusEnum::indexStatuses()
            );
        }

        if (request()->query('sort') === null) {
            $eloquent->orderByRaw(
                'CASE hostel_room_allocations.status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END',
                [
                    HostelAllocationStatusEnum::ACTIVE->value,
                    HostelAllocationStatusEnum::CHECKED_OUT->value,
                    HostelAllocationStatusEnum::CLOSED->value,
                ]
            )->latest('hostel_room_allocations.created_at');
        }

        return $builder;
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }

    /**
     * @return list<array{id: int, name: string, slug: string, marketValue: ?float}>
     */
    private function amenitiesForAllocation(HostelRoomAllocation $allocation): array
    {
        $sectionAmenities = $allocation->section?->amenities;
        $amenities = ($sectionAmenities !== null && $sectionAmenities->isNotEmpty())
            ? $sectionAmenities
            : $allocation->room?->amenities;

        if ($amenities === null || $amenities->isEmpty()) {
            return [];
        }

        return $amenities
            ->map(fn ($amenity): array => [
                'id' => (int) $amenity->id,
                'name' => (string) $amenity->name,
                'slug' => (string) $amenity->slug,
                'marketValue' => $amenity->market_value !== null ? (float) $amenity->market_value : null,
            ])
            ->values()
            ->all();
    }
}
