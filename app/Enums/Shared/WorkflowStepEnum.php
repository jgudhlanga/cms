<?php

namespace App\Enums\Shared;

enum WorkflowStepEnum: string
{
    case SUBMITTED = 'submitted';
    case AWAITING_APPLICATION_FEE_PAYMENT = 'awaiting_application_fee_payment';
    case IN_REVIEW = 'in_review';
    case AWAITING_REQUIREMENTS = 'awaiting_requirements';
    case INTERVIEW_SCHEDULED = 'interview_scheduled';
    case INTERVIEW_COMPLETED = 'interview_completed';
    case DECISION_PENDING = 'decision_pending';
    case ACCEPTED_OFFER_MADE = 'accepted_offer_made';
    case WAITLISTED = 'waitlisted';
    case REJECTED = 'rejected';
    case OFFER_ACCEPTED = 'offer_accepted';
    case OFFER_DECLINED = 'offer_declined';
    case AWAITING_FEES_PAYMENT = 'awaiting_fees_payment';
    case ENROLLED_REGISTERED = 'enrolled_registered';

    public function name(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Submitted',
            self::AWAITING_REQUIREMENTS => 'Awaiting Requirements',
            self::IN_REVIEW => 'In Review',
            self::AWAITING_APPLICATION_FEE_PAYMENT => 'Awaiting Application Fee Payment',
            self::INTERVIEW_SCHEDULED => 'Interview Scheduled',
            self::INTERVIEW_COMPLETED => 'Interview Completed',
            self::DECISION_PENDING => 'Decision Pending',
            self::ACCEPTED_OFFER_MADE => 'Accepted / Offer Made',
            self::WAITLISTED => 'Waitlisted',
            self::REJECTED => 'Rejected',
            self::OFFER_ACCEPTED => 'Offer Accepted',
            self::OFFER_DECLINED => 'Offer Declined',
            self::AWAITING_FEES_PAYMENT => 'Awaiting fees payment',
            self::ENROLLED_REGISTERED => 'Enrolled / Registered',
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::SUBMITTED => 1,
            self::AWAITING_APPLICATION_FEE_PAYMENT => 2,
            self::IN_REVIEW => 3,
            self::AWAITING_REQUIREMENTS => 4,
            self::INTERVIEW_SCHEDULED => 5,
            self::INTERVIEW_COMPLETED => 6,
            self::DECISION_PENDING => 7,
            self::ACCEPTED_OFFER_MADE => 8,
            self::WAITLISTED => 9,
            self::REJECTED => 10,
            self::OFFER_ACCEPTED => 11,
            self::OFFER_DECLINED => 12,
            self::AWAITING_FEES_PAYMENT => 13,
            self::ENROLLED_REGISTERED => 14,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUBMITTED => 'Application has been submitted and is awaiting review.',
            self::AWAITING_APPLICATION_FEE_PAYMENT => 'Pending payment of application or registration fees.',
            self::IN_REVIEW => 'Application is currently under review by staff.',
            self::AWAITING_REQUIREMENTS => 'Additional documents or info required.',
            self::INTERVIEW_SCHEDULED => 'Interview has been scheduled with the applicant.',
            self::INTERVIEW_COMPLETED => 'Interview has been completed and is under consideration.',
            self::DECISION_PENDING => 'A final admission decision is being made.',
            self::ACCEPTED_OFFER_MADE => 'Offer has been made to the applicant.',
            self::WAITLISTED => 'Applicant has been waitlisted.',
            self::REJECTED => 'Application has been rejected.',
            self::OFFER_ACCEPTED => 'Offer has been accepted by the applicant.',
            self::OFFER_DECLINED => 'Applicant declined the offer.',
            self::AWAITING_FEES_PAYMENT => 'Awaiting fees payment.',
            self::ENROLLED_REGISTERED => 'Applicant has enrolled and completed registration.',
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
