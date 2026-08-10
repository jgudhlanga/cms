<?php

declare(strict_types=1);

namespace App\Enums\Students;

enum StudentClearanceSection: string
{
    case Accounts = 'accounts';
    case Library = 'library';
    case Security = 'security';
    case Hostel = 'hostel';
    case Department = 'department';

    public function permission(): string
    {
        return 'student-clearance:'.$this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Accounts => __('trans.clearance_accounts'),
            self::Library => __('trans.clearance_library'),
            self::Security => __('trans.clearance_security'),
            self::Hostel => __('trans.clearance_hostel'),
            self::Department => __('trans.clearance_department'),
        };
    }

    public function clearedColumn(): string
    {
        return $this->value.'_cleared';
    }

    public function clearedByColumn(): string
    {
        return $this->value.'_cleared_by';
    }

    public function clearedAtColumn(): string
    {
        return $this->value.'_cleared_at';
    }

    public function notesColumn(): string
    {
        return $this->value.'_notes';
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }
}
