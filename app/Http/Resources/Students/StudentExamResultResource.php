<?php

declare(strict_types=1);

namespace App\Http\Resources\Students;

use App\Models\Students\StudentExamResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StudentExamResult
 */
class StudentExamResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidateNumber' => $this->candidate_number,
            'calendarYear' => $this->calendar_year,
            'session' => $this->session,
            'comment' => $this->comment?->value,
            'rawCourseComment' => $this->raw_course_comment,
            'commentNeedsReview' => $this->comment_needs_review,
        ];
    }
}
