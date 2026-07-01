<?php

declare(strict_types=1);

namespace App\Http\Resources\Maintenance;

use App\Models\Students\StudentApplication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read string|null $paymentEligibility
 * @property-read bool|null $hasMatchingPayment
 *
 * @mixin StudentApplication
 */
class VerifiedStudentForFinalEnrolmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'maintenance-verified-student-final-enrolment',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->student?->user?->full_name,
                'email' => $this->student?->user?->email,
                'studentNumber' => $this->student?->student_number,
                'idNumber' => $this->student?->id_number,
                'department' => $this->institutionDepartment?->department?->name,
                'course' => $this->departmentCourse?->course?->name,
                'level' => $this->departmentLevel?->level?->name,
                'classListId' => isset($this->classListId) ? (int) $this->classListId : null,
                'studentId' => $this->student_id,
                'paymentEligibility' => $this->paymentEligibility ?? 'no_payment',
                'hasMatchingPayment' => (bool) ($this->hasMatchingPayment ?? false),
            ],
        ];
    }
}
