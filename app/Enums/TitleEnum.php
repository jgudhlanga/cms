<?php

namespace App\Enums;

enum TitleEnum: string
{
	case MR = 'Mr';
	case MRS = 'Mrs';
	case MISS = 'Miss';
	case DR = 'Dr';
	case PROF = 'Prof';

	public function label(): string
	{
		return match ($this) {
			self::MR => 'Mr',
			self::MRS => 'Mrs',
			self::MISS => 'Miss',
			self::DR => 'Dr',
			self::PROF => 'Prof',
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
