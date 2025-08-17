<?php

namespace App\Enums\Shared;

enum WorkflowStepActionEnum: string
{
    case SEND_EMAIL_TO_APPLICANT = 'send_email_to_applicant';
    case GENERATE_STUDENT_NUMBER = 'generate_student_number';
    case SEND_EMAIL_TO_STAFF = 'send_email_to_staff';
    case CREATE_PAYMENT_LINK = 'create_payment_link';
    case REQUEST_DOCUMENTS = 'request_documents';
    case VERIFY_APPLICATION_FEE_PAYMENT_WITH_ACCOUNTS = 'verify_application_fee_payment_with_accounts';
    case VERIFY_TUITION_FEE_PAYMENT_WITH_ACCOUNTS = 'verify_tuition_fee_payment_with_accounts';
    case GENERATE_OFFER_LETTER = 'generate_offer_letter`';
    case UPLOAD_RECEIPT = 'upload_receipt';
    case ASSIGN_STAFF = 'assign_staff';

    public function title(): string
    {
        return match ($this) {
            self::SEND_EMAIL_TO_APPLICANT => 'Send Email To Applicant',
            self::GENERATE_STUDENT_NUMBER => 'Generate Student Number',
            self::SEND_EMAIL_TO_STAFF => 'Send Email To Staff',
            self::CREATE_PAYMENT_LINK => 'Create Payment Link',
            self::REQUEST_DOCUMENTS => 'Request Documents',
            self::UPLOAD_RECEIPT => 'Upload Receipt',
            self::GENERATE_OFFER_LETTER => 'Generate Offer Letter',
            self::ASSIGN_STAFF => 'Assign Staff',
            self::VERIFY_APPLICATION_FEE_PAYMENT_WITH_ACCOUNTS => 'Verify Application Fee Payment with Accounts',
            self::VERIFY_TUITION_FEE_PAYMENT_WITH_ACCOUNTS => 'Verify Tuition Fee Payment with Accounts',
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
