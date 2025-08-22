<?php

namespace App\Enums\Shared;

enum WorkflowStepEnum: string
{
    case APPLICATION_SUBMITTED = 'application_submitted';
    case AWAITING_APPLICATION_FEE_PAYMENT = 'awaiting_application_fee_payment';
    case APPLICATION_IN_REVIEW = 'application_in_review';
    case AWAITING_REQUIREMENTS = 'awaiting_requirements';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
    case INTERVIEW_COMPLETED = 'interview_completed';
    case APPLICATION_ACCEPTED_AND_OFFER_MADE = 'application_accepted_and_offer_made';
    case APPLICATION_REJECTED = 'application_rejected';
    case APPLICATION_WAITLISTED = 'application_waitlisted';
    case APPLICANT_ACCEPTED_OFFER = 'applicant_accepted_offer';
    case APPLICANT_REJECTED_OFFER = 'applicant_rejected_offer';
    case AWAITING_TUITION_FEES_PAYMENT = 'awaiting_tuition_fees_payment';
    case FINAL_DECISION_PENDING = 'final_decision_pending';
    case STUDENT_ENROLLED = 'student_enrolled';

    public function name(): string
    {
        return match ($this) {
            self::APPLICATION_SUBMITTED => 'Application submitted',
            self::AWAITING_APPLICATION_FEE_PAYMENT => 'Awaiting application fee payment',
            self::APPLICATION_IN_REVIEW => 'Application in review',
            self::AWAITING_REQUIREMENTS => 'Awaiting requirements',
            self::INTERVIEW_SCHEDULED => 'Interview scheduled',
            self::INTERVIEW_COMPLETED => 'Interview completed',
            self::APPLICATION_ACCEPTED_AND_OFFER_MADE => 'Application accepted and offer made',
            self::APPLICATION_REJECTED => 'Application rejected',
            self::APPLICANT_ACCEPTED_OFFER => 'Applicant accepted offer',
            self::APPLICANT_REJECTED_OFFER => 'Applicant rejected offer',
            self::APPLICATION_WAITLISTED => 'Application waitlisted',
            self::AWAITING_TUITION_FEES_PAYMENT => 'Awaiting tuition fee payment',
            self::FINAL_DECISION_PENDING => 'Final decision pending',
            self::STUDENT_ENROLLED => 'Student enrolled',
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::APPLICATION_SUBMITTED => 1,
            self::AWAITING_APPLICATION_FEE_PAYMENT => 2,
            self::APPLICATION_IN_REVIEW => 3,
            self::AWAITING_REQUIREMENTS => 4,
            self::INTERVIEW_SCHEDULED => 5,
            self::INTERVIEW_COMPLETED => 6,
            self::APPLICATION_ACCEPTED_AND_OFFER_MADE => 7,
            self::APPLICATION_REJECTED => 8,
            self::APPLICANT_ACCEPTED_OFFER => 9,
            self::APPLICANT_REJECTED_OFFER => 10,
            self::APPLICATION_WAITLISTED => 11,
            self::AWAITING_TUITION_FEES_PAYMENT => 12,
            self::FINAL_DECISION_PENDING => 13,
            self::STUDENT_ENROLLED => 14,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::APPLICATION_SUBMITTED => 'The applicant has successfully submitted their application.',
            self::AWAITING_APPLICATION_FEE_PAYMENT => 'The application fee has not yet been paid by the applicant.',
            self::APPLICATION_IN_REVIEW => 'The selection officers are currently reviewing the submitted application.',
            self::AWAITING_REQUIREMENTS => 'The applicant needs to provide additional documents or requirements.',
            self::INTERVIEW_SCHEDULED => 'An interview has been scheduled for the applicant.',
            self::INTERVIEW_COMPLETED => 'The interview with the applicant has been completed and is awaiting outcome.',
            self::APPLICATION_ACCEPTED_AND_OFFER_MADE => 'The application has been accepted and an offer has been made to the applicant.',
            self::APPLICATION_REJECTED => 'The application has been reviewed and was not successful.',
            self::APPLICANT_ACCEPTED_OFFER => 'The applicant has accepted the admission offer.',
            self::APPLICANT_REJECTED_OFFER => 'The applicant has declined the admission offer.',
            self::APPLICATION_WAITLISTED => 'The applicant has been placed on the waitlist and may be reconsidered later.',
            self::AWAITING_TUITION_FEES_PAYMENT => 'The applicant has accepted the offer and is required to pay tuition fees.',
            self::FINAL_DECISION_PENDING => 'The selection officers are currently considering the final decision.',
            self::STUDENT_ENROLLED => 'The applicant has paid tuition fees and is now officially enrolled as a student.',
        };
    }


    public static function all(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'name' => $case->name(),
                'position' => $case->position(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
