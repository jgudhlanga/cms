<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\Student;
use App\Services\Maintenance\Students\FaultyStudentIdNumberAnalysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read array<string, mixed>|null $faultyIdAnalysis
 *
 * @mixin Student
 */
class FaultyStudentIdNumberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $analysis = $this->faultyIdAnalysis ?? app(FaultyStudentIdNumberAnalysis::class)->analyze($this->resource);

        return [
            'type' => 'maintenance-faulty-student-id-number',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->user?->full_name,
                'email' => $this->user?->email,
                'phoneNumber' => $this->user?->phone_number,
                'studentNumber' => $this->student_number,
                'idNumber' => (string) $this->id_number,
                'suggestedIdNumber' => $analysis['suggestedIdNumber'],
                'proposedIdNumber' => $analysis['proposedIdNumber'],
                'rectificationStatus' => $analysis['rectificationStatus'],
                'conflict' => $analysis['conflict'],
            ],
        ];
    }
}
