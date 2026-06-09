<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use App\Services\Enrollment\EnrollmentLookupService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Student */
class FaultyStudentIdNumberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currentIdNumber = (string) $this->id_number;
        $normalized = EnrollmentLookupService::normalizeNationalId($currentIdNumber);
        $suggestedIdNumber = ZimbabweanIdNumber::isValid($normalized) && $normalized !== strtoupper(trim($currentIdNumber))
            ? $normalized
            : null;

        return [
            'type' => 'maintenance-faulty-student-id-number',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->user?->full_name,
                'email' => $this->user?->email,
                'studentNumber' => $this->student_number,
                'idNumber' => $currentIdNumber,
                'suggestedIdNumber' => $suggestedIdNumber,
            ],
        ];
    }
}
