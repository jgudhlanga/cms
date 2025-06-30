<?php

namespace App\Enums\Institution;

enum ApplicationStepEnum: string
{
    case DRAFT_INCOMPLETE = 'Draft / Incomplete';
    case SUBMITTED = 'Submitted';
    case IN_REVIEW = 'In Review';
    case AWAITING_REQUIREMENTS = 'Awaiting Requirements';
    case AWAITING_PAYMENT = 'Awaiting Payment';
    case INTERVIEW_SCHEDULED = 'Interview Scheduled';
    case INTERVIEW_COMPLETED = 'Interview Completed';
    case DECISION_PENDING = 'Decision Pending';
    case ACCEPTED_OFFER_MADE = 'Accepted / Offer Made';
    case WAITLISTED = 'Waitlisted';
    case REJECTED = 'Rejected';
    case OFFER_ACCEPTED = 'Offer Accepted';

    case OFFER_DECLINED = 'Offer Declined';
    case ENROLLED_REGISTERED = 'Enrolled / Registered';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT_INCOMPLETE => 'Daft / Incomplete',
            self::SUBMITTED => 'Submitted',
            self::IN_REVIEW => 'In Review',
            self::AWAITING_REQUIREMENTS => 'Awaiting Requirements',
            self::AWAITING_PAYMENT => 'Awaiting Payment',
            self::INTERVIEW_SCHEDULED => 'Interview Scheduled',
            self::INTERVIEW_COMPLETED => 'Interview Completed',
            self::DECISION_PENDING => 'Decision Pending',
            self::ACCEPTED_OFFER_MADE => 'Accepted / Offer Made',
            self::WAITLISTED => 'Waitlisted',
            self::REJECTED => 'Rejected',
            self::OFFER_ACCEPTED => 'Offer Accepted',
            self::OFFER_DECLINED => 'Offer Declined',
            self::ENROLLED_REGISTERED => 'Enrolled / Registered',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }
}
