<?php


namespace App\Enums\Shared;

enum DocumentTypeEnum: string
{
    case ADMISSION_LETTER = 'admission_letter';
    case APPLICATION_FORM = 'application_form';
    case BIRTH_CERTIFICATE = 'birth_certificate';
    case CURRICULUM_VITAE = 'curriculum_vitae';
    case DRIVERS_LICENCE_PHOTO = 'drivers_licence_photo';
    case EDUCATIONAL_CERTIFICATE = 'educational_certificate';
    case IDENTITY_DOCUMENT = 'identity_document';
    case MEDICAL_REPORT = 'medical_report';
    case NATIONAL_EXAM_CERTIFICATE = 'national_exam_certificate';
    case OFFER_LETTER = 'offer_letter';
    case PASSPORT_PHOTO = 'passport_photo';
    case PROOF_OF_PAYMENT = 'proof_of_payment';
    case RECEIPT = 'receipt';
    case RECOMMENDATION_LETTER = 'recommendation_letter';
    case TRANSCRIPT = 'transcript';
    case OTHER = 'other';

    public function name(): string
    {
        return match ($this) {
            self::ADMISSION_LETTER => 'Admission Letter',
            self::APPLICATION_FORM => 'Application Form',
            self::BIRTH_CERTIFICATE => 'Birth Certificate',
            self::CURRICULUM_VITAE => 'Curriculum Vitae (CV)',
            self::DRIVERS_LICENCE_PHOTO => 'Driver’s Licence Photo',
            self::EDUCATIONAL_CERTIFICATE => 'Educational Certificate',
            self::IDENTITY_DOCUMENT => 'Identity Document',
            self::MEDICAL_REPORT => 'Medical Report',
            self::NATIONAL_EXAM_CERTIFICATE => 'National Exam Certificate',
            self::OFFER_LETTER => 'Offer Letter',
            self::PASSPORT_PHOTO => 'Passport Photo',
            self::PROOF_OF_PAYMENT => 'Proof of Payment',
            self::RECEIPT => 'Receipt',
            self::RECOMMENDATION_LETTER => 'Recommendation Letter',
            self::TRANSCRIPT => 'Academic Transcript',
            self::OTHER => 'Other',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::ADMISSION_LETTER => 'Official letter confirming acceptance into the institution.',
            self::APPLICATION_FORM => 'Completed form submitted during application.',
            self::BIRTH_CERTIFICATE => 'Government-issued certificate of birth.',
            self::CURRICULUM_VITAE => 'Detailed summary of academic and professional background.',
            self::DRIVERS_LICENCE_PHOTO => 'Photograph of a valid driver’s licence for identification.',
            self::EDUCATIONAL_CERTIFICATE => 'Any certified academic certificate (e.g., diploma, degree).',
            self::IDENTITY_DOCUMENT => 'National ID, driver’s licence, or valid passport.',
            self::MEDICAL_REPORT => 'Certified health or fitness report for enrollment requirements.',
            self::NATIONAL_EXAM_CERTIFICATE => 'Certificate of completion from a recognized national exam.',
            self::OFFER_LETTER => 'Offer letter.',
            self::PASSPORT_PHOTO => 'Recent passport-sized photograph for identification.',
            self::PROOF_OF_PAYMENT => 'Receipt or confirmation of fee payment.',
            self::RECEIPT => 'Issued acknowledgment of a payment made.',
            self::RECOMMENDATION_LETTER => 'Letter of recommendation from a teacher, employer, or authority.',
            self::TRANSCRIPT => 'Official record of academic grades and performance.',
            self::OTHER => 'Miscellaneous type not on other categories.',
        };
    }

    public static function all(): array
    {
        $cases = self::cases();

        // Sort alphabetically by name
        usort($cases, fn($a, $b) => strcmp($a->name(), $b->name()));

        return array_map(fn($case) => [
            'value' => $case->value,
            'name' => $case->name(),
            'description' => $case->description(),
        ], $cases);
    }
}
