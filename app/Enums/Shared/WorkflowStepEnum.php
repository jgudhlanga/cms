<?php

namespace App\Enums\Shared;

enum WorkflowStepEnum: string
{
    case SUBMITTED = 'submitted';
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
            self::SUBMITTED => 'Submitted',
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
            self::SUBMITTED => 1,
            self::APPLICATION_IN_REVIEW => 2,
            self::AWAITING_REQUIREMENTS => 3,
            self::INTERVIEW_SCHEDULED => 4,
            self::INTERVIEW_COMPLETED => 5,
            self::APPLICATION_ACCEPTED_AND_OFFER_MADE => 6,
            self::APPLICATION_REJECTED => 7,
            self::APPLICANT_ACCEPTED_OFFER => 8,
            self::APPLICANT_REJECTED_OFFER => 9,
            self::APPLICATION_WAITLISTED => 10,
            self::AWAITING_TUITION_FEES_PAYMENT => 11,
            self::FINAL_DECISION_PENDING => 12,
            self::STUDENT_ENROLLED => 13,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUBMITTED => 'The application has been submitted and is awaiting review.',
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
