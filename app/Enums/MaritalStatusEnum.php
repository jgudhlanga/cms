<?php

namespace App\Enums;

enum MaritalStatusEnum: string
{
    case DIVORCED = 'Divorced';
    case ENGAGED = 'ENGAGED';
    case MARRIED = 'Married';
	case SINGLE = 'Single';
	case WIDOWED = 'Widowed';

	// extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
	public function label(): string
	{
		return match ($this) {
			self::DIVORCED => 'Divorced',
            self::ENGAGED => 'Engaged',
            self::MARRIED => 'Married',
            self::SINGLE => 'Single',
            self::WIDOWED => 'Widowed',
        };
	}

	public static function all(): array
	{
		return array_combine(
			array_column(self::cases(), 'value'),
			array_map(fn($case) => $case->label(), self::cases())
		);
	}
}
