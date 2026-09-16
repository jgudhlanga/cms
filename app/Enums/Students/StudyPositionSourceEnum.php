<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum StudyPositionSourceEnum: string
{
    case STUDENT = 'student';
    case ADMIN = 'admin';
    case AUTO_EXAM_PROCEED = 'auto_exam_proceed';
    case AUTO_NEW_INTAKE = 'auto_new_intake';
    case AUTO_DEPARTMENT_RECONCILIATION = 'auto_department_reconciliation';
    case AUTO_CLASS_LIST = 'auto_class_list';

    public function label(): string
    {
        return __('students.study_position_source_'.$this->value);
    }

    /**
     * A person stated this position; automatic rules must never overwrite it.
     */
    public function isHuman(): bool
    {
        return $this === self::STUDENT || $this === self::ADMIN;
    }

    /**
     * Staff-verified department data: may replace an automatic answer, but not a person's.
     */
    public function isDepartment(): bool
    {
        return $this === self::AUTO_DEPARTMENT_RECONCILIATION;
    }

    /**
     * Inferred by the system from evidence already on record.
     */
    public function isAutomatic(): bool
    {
        return ! $this->isHuman() && ! $this->isDepartment();
    }
}
