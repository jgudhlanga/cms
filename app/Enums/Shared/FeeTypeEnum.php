<?php

namespace App\Enums\Shared;

use App\Models\HMS\HostelApplication;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Illuminate\Support\Str;

enum FeeTypeEnum: string
{
    case EXAMINATION_FEE = 'examination_fee';
    case GRADUATION_FEE = 'graduation_fee';
    case GUEST_ACCOMMODATION_FEE = 'guest_accommodation_fee';
    case LABORATORY_FEE = 'laboratory_fee';
    case LIBRARY_FEE = 'library_fee';
    case PENALTY_FEE = 'penalty_fee';
    case APPLICATION_FEE = 'application_fee';
    case STUDENT_ACCOMMODATION_FEE = 'student_accommodation_fee';
    case STUDENT_ID_FEE = 'student_id_fee';
    case TUITION_FEE = 'tuition_fee';
    case OTHER_FEE = 'other_fee';

    public function name(): string
    {
        return match ($this) {
            self::EXAMINATION_FEE => 'Examination Fee',
            self::GRADUATION_FEE => 'Graduation Fee',
            self::GUEST_ACCOMMODATION_FEE => 'Guest Accommodation Fee',
            self::LABORATORY_FEE => 'Laboratory Fee',
            self::LIBRARY_FEE => 'Library Fee',
            self::PENALTY_FEE => 'Penalty Fee',
            self::APPLICATION_FEE => 'Application Fee',
            self::STUDENT_ACCOMMODATION_FEE => 'Student Accommodation Fee',
            self::STUDENT_ID_FEE => 'Student ID Fee',
            self::TUITION_FEE => 'Tuition Fee',
            self::OTHER_FEE => 'Other Fee',
        };
    }

    public function position(): string
    {
        return match ($this) {
            self::TUITION_FEE => 1,
            self::APPLICATION_FEE => 2,
            self::EXAMINATION_FEE => 3,
            self::GRADUATION_FEE => 4,
            self::STUDENT_ID_FEE => 5,
            self::PENALTY_FEE => 6,
            self::STUDENT_ACCOMMODATION_FEE => 7,
            self::GUEST_ACCOMMODATION_FEE => 8,
            self::LABORATORY_FEE => 9,
            self::LIBRARY_FEE => 10,
            self::OTHER_FEE => 11,
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::EXAMINATION_FEE => 'Charges for sitting exams or assessments.',
            self::GRADUATION_FEE => 'Covers graduation ceremony and certification costs.',
            self::GUEST_ACCOMMODATION_FEE => 'Charges for guest lodging or temporary housing.',
            self::LABORATORY_FEE => 'Covers the use of labs, materials, and equipment.',
            self::LIBRARY_FEE => 'Access to library facilities and resources.',
            self::PENALTY_FEE => 'Fines for late payments, misconduct, or breaches.',
            self::APPLICATION_FEE => 'Fee required upon enrolment or course application.',
            self::STUDENT_ACCOMMODATION_FEE => 'Charges for on-campus student housing.',
            self::STUDENT_ID_FEE => 'Fee for issuing and maintaining student ID cards.',
            self::TUITION_FEE => 'Covers the cost of instruction for courses.',
            self::OTHER_FEE => 'Miscellaneous charges not covered under other categories.',
        };
    }

    public function slug(): string
    {
        return Str::slug($this->name);
    }

    public static function fromFeeType(FeeType $feeType): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $feeType->slug) {
                return $case;
            }
        }

        return null;
    }

    public function ledgerableClass(): string
    {
        return match ($this) {
            self::APPLICATION_FEE => User::class,
            self::STUDENT_ACCOMMODATION_FEE, self::GUEST_ACCOMMODATION_FEE => HostelApplication::class,
            self::TUITION_FEE => StudentProgram::class,
            default => User::class,
        };
    }

    public function requiresLedgerableId(): bool
    {
        return $this === self::TUITION_FEE;
    }

    public function postPaymentRoute(): string
    {
        return match ($this) {
            self::APPLICATION_FEE => 'portal.application.create',
            self::STUDENT_ACCOMMODATION_FEE, self::GUEST_ACCOMMODATION_FEE => 'portal.profile.accommodations',
            self::TUITION_FEE => 'portal.profile.financials',
            default => 'portal.dashboard',
        };
    }

    public function isAccommodationFee(): bool
    {
        return in_array($this, [self::STUDENT_ACCOMMODATION_FEE, self::GUEST_ACCOMMODATION_FEE], true);
    }

    public static function all(): array
    {
        $cases = self::cases();

        // Sort by label alphabetically
        usort($cases, fn ($a, $b) => strcmp($a->name(), $b->name()));

        return array_map(fn ($case) => [
            'value' => $case->value,
            'name' => $case->name(),
            'description' => $case->description(),
        ], $cases);
    }
}
