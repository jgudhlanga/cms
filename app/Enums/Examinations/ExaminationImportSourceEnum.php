<?php

namespace App\Enums\Examinations;

enum ExaminationImportSourceEnum: string
{
    case Upload = 'upload';
    case Watcher = 'watcher';

    public function label(): string
    {
        return match ($this) {
            self::Upload => __('examinations.import_source_upload'),
            self::Watcher => __('examinations.import_source_watcher'),
        };
    }
}
