<?php

declare(strict_types=1);

namespace App\Http\Resources\Students;

use App\Enums\Students\StudentClearanceSection;
use App\Models\Students\StudentClearance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentClearance
 */
class StudentClearanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sections = [];

        foreach (StudentClearanceSection::all() as $section) {
            $clearedByRelation = $section->value.'ClearedBy';
            $clearedBy = $this->relationLoaded($clearedByRelation)
                ? $this->{$clearedByRelation}
                : null;

            $sections[] = [
                'key' => $section->value,
                'label' => $section->label(),
                'help' => __('trans.clearance_'.$section->value.'_help'),
                'cleared' => (bool) $this->getAttribute($section->clearedColumn()),
                'notes' => $this->getAttribute($section->notesColumn()),
                'clearedBy' => $clearedBy?->full_name,
                'clearedAt' => $this->getAttribute($section->clearedAtColumn())?->toIso8601String(),
                'canEdit' => $request->user()?->can($section->permission()) ?? false,
            ];
        }

        return [
            'id' => $this->id,
            'studentId' => $this->student_id,
            'calendarYear' => $this->calendar_year,
            'semesterId' => $this->semester_id,
            'isFullyCleared' => $this->isFullyCleared(),
            'pendingSections' => $this->pendingSections(),
            'sections' => $sections,
            'identity' => [
                'idNumber' => $this->whenLoaded('student', fn () => $this->student?->id_number),
                'passportNumber' => $this->whenLoaded('student', fn () => $this->student?->passport_number),
                'studentNumber' => $this->whenLoaded('student', fn () => $this->student?->student_number),
            ],
        ];
    }
}
