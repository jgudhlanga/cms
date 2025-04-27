<?php

namespace App\Enums;

enum GenderEnum: string
{
	case MALE = 'Male';
	case FEMALE = 'Female';

	public function label(): string
	{
		return match ($this) {
			self::MALE => 'Male',
			self::FEMALE => 'Female',
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

