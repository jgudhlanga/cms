<?php

namespace App\Http\Resources\Enrolments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentApplicationResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'applicationId' => $this->application_id,
            'applicationTrackingNumber' => $this->application_tracking_number,
            'applicationDate' => $this->application_date,
            'studentId' => $this->student_id,
            'studentName' => $this->student_name,
            'studentNumber' => $this->student_number,
            'email' => $this->email,
            'phoneNumber' => $this->phone_number,
            'gender' => $this->gender,
            'disabilityStatus' => $this->disability_status,
            'workflowStep' => $this->workflow_step,
            'receiptId' => $this->receipt_id,
            'receiptAmount' => $this->receipt_amount,
            'examSittingsCount' => $this->exam_sittings_count,
            'firstExamYear' => $this->first_exam_year,
            'inClassList' => $this->in_class_list,
            'classListType' => $this->class_list_type,
            'requiredLevelCompleted' => $this->required_level_completed,
            'readWriteAcknowledged' => $this->read_write_acknowledged,
            'offerAccepted' => $this->offer_accepted,
            'academicResults' => OLevelResultResource::collection($this->academic_results ?? collect()),
        ];
    }
}
