<?php

namespace App\Enums\Shared;

enum WorkflowStepActionEnum: string
{
    case SEND_EMAIL = 'send_email';
    case CREATE_PAYMENT_LINK = 'create_payment_link';
    case REQUEST_DOCUMENTS = 'request_documents';
    case VERIFY_IDENTITY = 'verify_identity';
    case MARK_STEP_COMPLETE = 'mark_step_complete';
    case REVERT_STEP = 'revert_step';
    case UPLOAD_RECEIPT = 'upload_receipt';
    case ADD_NOTE = 'add_note';
    case NOTIFY_APPLICANT = 'notify_applicant';
    case ASSIGN_STAFF = 'assign_staff';

    public function title(): string
    {
        return match ($this) {
            self::SEND_EMAIL => 'Send Email',
            self::CREATE_PAYMENT_LINK => 'Create Payment Link',
            self::REQUEST_DOCUMENTS => 'Request Documents',
            self::VERIFY_IDENTITY => 'Verify Identity',
            self::MARK_STEP_COMPLETE => 'Mark Step Complete',
            self::REVERT_STEP => 'Revert Step',
            self::UPLOAD_RECEIPT => 'Upload Receipt',
            self::ADD_NOTE => 'Add Internal Note',
            self::NOTIFY_APPLICANT => 'Notify Applicant',
            self::ASSIGN_STAFF => 'Assign Staff',
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
