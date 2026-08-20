<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PastelLinkedStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->resource->student;
        $user = $student?->user;
        $linkedBy = $this->resource->linkedBy;

        $nameParts = array_filter([
            $user?->last_name,
            $user?->first_name,
        ]);

        return [
            'type' => 'pastelLinkedStudent',
            'id' => $this->resource->id,
            'attributes' => [
                'studentId' => $this->resource->student_id,
                'studentNumber' => $this->resource->student_number ?? $student?->student_number,
                'studentName' => $nameParts !== [] ? implode(' ', $nameParts) : null,
                'intakePeriodId' => $this->resource->intake_period_id,
                'intakePeriodName' => $this->resource->intakePeriod?->name,
                'linkedByName' => $linkedBy !== null
                    ? trim(($linkedBy->first_name ?? '').' '.($linkedBy->last_name ?? ''))
                    : null,
                'linkedAt' => $this->resource->linked_at?->toIso8601String(),
                'createdAt' => $this->resource->created_at?->toIso8601String(),
            ],
        ];
    }
}
