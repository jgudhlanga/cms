<?php

namespace App\Enums\Shared;

use Illuminate\Support\Str;

enum WorkflowStepEnum: string
{
    case REGISTRATION_FEE = 'registration-fee';
    case REVIEW = 'review';
    case REQUIREMENTS = 'requirements';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case WAITLISTED = 'waitlisted';
    case ENROLLED = 'enrolled';

    public function name(): string
    {
        return match ($this) {
            self::REGISTRATION_FEE => 'Registration Fee',
            self::REVIEW => 'Review',
            self::REQUIREMENTS => 'Requirements',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
            self::WAITLISTED => 'Waitlisted',
            self::ENROLLED => 'Enrolled',
        };
    }

    public function position(): int
    {
        return match ($this) {
            self::REGISTRATION_FEE => 1,
            self::REVIEW => 2,
            self::REQUIREMENTS => 3,
            self::ACCEPTED => 4,
            self::REJECTED => 5,
            self::WAITLISTED => 6,
            self::ENROLLED => 7,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::REGISTRATION_FEE => 'Student must pay the registration fee before proceeding.',
            self::REVIEW => 'Application is under review by the admissions team.',
            self::REQUIREMENTS => 'Applicant must submit required documents or complete requirements.',
            self::ACCEPTED => 'Application has been accepted and the student may proceed to enrollment.',
            self::REJECTED => 'Application has been rejected.',
            self::WAITLISTED => 'Applicant has been waitlisted pending available space.',
            self::ENROLLED => 'Student has completed all requirements and is officially enrolled.',
        };
    }

    public function slug(): string
    {
        return Str::slug($this->name());
    }

    public static function all(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'name' => $case->name(),
                'slug' => $case->slug(),
                'position' => $case->position(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}
