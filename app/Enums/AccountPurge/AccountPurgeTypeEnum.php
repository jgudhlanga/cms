<?php

declare(strict_types=1);

namespace App\Enums\AccountPurge;

enum AccountPurgeTypeEnum: string
{
    case STUDENT_ACCOUNT = 'student_account';
    case USER_ACCOUNT = 'user_account';

    public function label(): string
    {
        return match ($this) {
            self::STUDENT_ACCOUNT => __('trans.maintenance_archives_type_student_account'),
            self::USER_ACCOUNT => __('trans.maintenance_archives_type_user_account'),
        };
    }

    public static function tryFromFilter(?string $value): ?self
    {
        if ($value === null || trim($value) === '' || $value === 'all') {
            return null;
        }

        return self::tryFrom($value);
    }
}
