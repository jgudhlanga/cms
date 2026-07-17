<?php

namespace App\Enums\Examinations;

enum ExaminationImportStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => __('examinations.import_status_pending'),
            self::Processing => __('examinations.import_status_processing'),
            self::Completed => __('examinations.import_status_completed'),
            self::Failed => __('examinations.import_status_failed'),
            self::Cancelled => __('examinations.import_status_cancelled'),
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed, self::Cancelled], true);
    }
}
