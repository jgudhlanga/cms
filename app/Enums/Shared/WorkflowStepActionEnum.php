<?php

namespace App\Enums\Shared;

enum WorkflowStepActionEnum: string
{
    case SEND_EMAIL_TO_APPLICANT = 'send_email_to_applicant';
    case GENERATE_APPLICATION_TRACKING_NUMBER = 'generate_application_tracking_number';
    case GENERATE_STUDENT_NUMBER = 'generate_student_number';
    case SEND_EMAIL_TO_STAFF = 'send_email_to_staff';
    case CREATE_PAYMENT_LINK = 'create_payment_link';
    case REQUEST_DOCUMENTS = 'request_documents';
    case VERIFY_PAYMENT_WITH_ACCOUNTS = 'verify_payment_with_accounts';
    case GENERATE_OFFER_LETTER = 'generate_offer_letter`';
    case VERIFY_IDENTITY = 'verify_identity';
    case UPLOAD_RECEIPT = 'upload_receipt';
    case ASSIGN_STAFF = 'assign_staff';

    public function title(): string
    {
        return match ($this) {
            self::SEND_EMAIL_TO_APPLICANT => 'Send Email To Applicant',
            self::GENERATE_APPLICATION_TRACKING_NUMBER => 'Generate Application Tracking Number',
            self::GENERATE_STUDENT_NUMBER => 'Generate Student Number',
            self::SEND_EMAIL_TO_STAFF => 'Send Email To Staff',
            self::CREATE_PAYMENT_LINK => 'Create Payment Link',
            self::REQUEST_DOCUMENTS => 'Request Documents',
            self::VERIFY_IDENTITY => 'Verify Identity',
            self::UPLOAD_RECEIPT => 'Upload Receipt',
            self::GENERATE_OFFER_LETTER => 'Generate Offer Letter',
            self::ASSIGN_STAFF => 'Assign Staff',
            self::VERIFY_PAYMENT_WITH_ACCOUNTS => 'Verify Payment with Accounts',
        };
    }

    public static function all(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->title(), self::cases())
        );
    }
}
