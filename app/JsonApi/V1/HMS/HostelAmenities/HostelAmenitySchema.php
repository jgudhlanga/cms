<?php

namespace App\JsonApi\V1\HMS\HostelAmenities;

use App\JsonApi\V1\HMS\Filters\TrashedFilter;
use App\JsonApi\V1\HMS\HostelAmenities\Filters\HostelAmenitySearchFilter;
use App\Models\HMS\HostelAmenity;
use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;

class HostelAmenitySchema extends Schema
{
    public static string $model = HostelAmenity::class;

    protected ?string $uriType = 'hms/hostel-amenities';

    protected ?array $defaultPagination = ['number' => 1, 'size' => 15];

    protected $defaultSort = 'name';

    public function fields(): array
    {
        return [
            ID::make(),
            Str::make('name')->sortable(),
            Str::make('slug')->sortable(),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
            DateTime::make('deletedAt', 'deleted_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            new HostelAmenitySearchFilter(),
            TrashedFilter::make(),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make()
            ->withDefaultPerPage((int) config('custom.system.pagination_items_per_page', 15));
    }
}
