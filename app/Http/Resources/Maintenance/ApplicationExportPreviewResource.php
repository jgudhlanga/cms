<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\StudentApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentApplication
 */
class ApplicationExportPreviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'maintenance-application-export-preview',
            'id' => $this->id,
            'attributes' => [
                'studentId' => $this->student_id,
                'name' => $this->student?->user?->full_name,
                'studentNumber' => $this->student?->student_number,
                'department' => $this->institutionDepartment?->department?->name,
                'level' => $this->departmentLevel?->level?->name,
                'course' => $this->departmentCourse?->course?->name,
                'modeOfStudy' => $this->modeOfStudy?->name,
                'intakePeriod' => $this->intakePeriod?->name,
                'applicationStatus' => $this->workflowStep?->name,
                'appliedAt' => $this->created_at?->toDateString(),
            ],
        ];
    }
}
