<?php

namespace App\Http\Resources\Institution;

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IntakePeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = data_get($this->resource, 'status');
        $status = $status instanceof BackedEnum ? $status->value : $status;

        return [
            'type' => 'intake-period',
            'id' => data_get($this->resource, 'id'),
            'attributes' => [
                'name' => data_get($this->resource, 'name'),
                'startDate' => data_get($this->resource, 'start_date'),
                'endDate' => data_get($this->resource, 'end_date'),
                'isActive' => data_get($this->resource, 'is_active'),
                'status' => $status,
                'isContinuous' => (bool) data_get($this->resource, 'is_continuous', false),
                'description' => data_get($this->resource, 'description'),
                $this->mergeWhen($request->routeIs('intake-periods.*'), [
                    'createdAt' => data_get($this->resource, 'created_at'),
                    'updatedAt' => data_get($this->resource, 'updated_at'),
                    'deletedAt' => data_get($this->resource, 'deleted_at'),
                ]),
            ],
        ];
    }
}
