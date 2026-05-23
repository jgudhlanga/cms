<?php

namespace App\JsonApi\V1\HMS\HmsSettings;

use App\Models\HMS\HmsSetting;
use Illuminate\Support\Facades\Auth;
use LaravelJsonApi\Eloquent\Fields\Boolean;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\QueryBuilder\JsonApiBuilder;
use LaravelJsonApi\Eloquent\Schema;

class HmsSettingSchema extends Schema
{
    public static string $model = HmsSetting::class;

    protected ?string $uriType = 'hms/hms-settings';

    public function fields(): array
    {
        return [
            ID::make(),
            Boolean::make('requireFullTimeStudy', 'require_full_time_study'),
            Str::make('fullTimeModeName', 'full_time_mode_name'),
            Boolean::make('requireTuitionPaid', 'require_tuition_paid'),
            Boolean::make('requireAddressOutsideCampus', 'require_address_outside_campus'),
            Str::make('campusCity', 'campus_city'),
            Boolean::make('allowGuests', 'allow_guests'),
            DateTime::make('createdAt', 'created_at')->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
        ];
    }

    public function newQuery($query = null): JsonApiBuilder
    {
        if ($tenantId = Auth::user()?->tenant_id) {
            HmsSetting::resolveForTenant($tenantId);
        }

        return parent::newQuery($query);
    }
}
