<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\StudentApplication;
use App\Services\Maintenance\Students\FaultyApplicationAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentApplication
 */
class FaultyApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'maintenance-faulty-application',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this->student_id,
                'name' => $this->student?->user?->full_name,
                'email' => $this->student?->user?->email,
                'studentNumber' => $this->student?->student_number,
                'trackingNumber' => $this->application_tracking_number,
                'department' => $this->institutionDepartment?->department?->name,
                'level' => $this->departmentLevel?->level?->name,
                'course' => $this->departmentCourse?->course?->name,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'intakePeriod' => $this->intakePeriod?->name,
                'applicationStatus' => $this->workflowStep?->name,
                'reasons' => app(FaultyApplicationAnalysis::class)->reasons($this->resource),
            ],
        ];
    }
}
