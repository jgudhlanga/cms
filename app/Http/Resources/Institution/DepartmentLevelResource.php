<?php

namespace App\Http\Resources\Institution;

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $institution_department_id
 */
class DepartmentLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['level', 'requirement']);

        $calendarType = $this->level?->calendar_type;
        $calendarTypeValue = $calendarType instanceof AcademicCalendarTypeEnum
            ? $calendarType->value
            : (is_string($calendarType) && $calendarType !== '' ? $calendarType : 'semester');

        return [
            'type' => 'department-level',
            'id' => $this->resource->id,
            'attributes' => [
                'institutionDepartmentId' => $this->institution_department_id,
                'levelId' => $this->level_id,
                'level' => $this->level?->name,
                'levelPosition' => $this->level?->position,
                'calendarType' => $calendarTypeValue,
                'description' => $this->resource->description,
                'showOnCurrentApplicationPeriod' => $this->resource->show_on_current_application_period,
                $this->mergeWhen($request->routeIs('department-levels.*'), [
                    'createdAt' => $this->resource->created_at,
                    'updatedAt' => $this->resource->updated_at,
                    'deletedAt' => $this->resource->deleted_at,
                ]),
            ],
            'relationships' => [
                'requirement' => DepartmentLevelRequirementResource::make($this->requirement),
            ],
        ];
    }
}
