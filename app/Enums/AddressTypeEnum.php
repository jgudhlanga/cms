<?php

namespace App\Enums;

enum AddressTypeEnum: string
{
	case BUSINESS = 'Business';
	case COMPLEX = 'Complex';
	case HOME = 'Home';
	case PHYSICAL = 'Physical';
	case POSTAL = 'Postal';

	public function label(): string
	{
		return match ($this) {
			self::BUSINESS => 'Business',
			self::COMPLEX => 'Complex',
			self::HOME => 'Home',
			self::PHYSICAL => 'Physical',
			self::POSTAL => 'Postal',
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
