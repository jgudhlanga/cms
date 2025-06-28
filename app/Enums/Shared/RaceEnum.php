<?php

namespace App\Enums\Shared;

enum RaceEnum: string
{
	case African = 'African';
	case Black = 'Black';
	case White = 'White';
	case Colored = 'Colored';
	case Indian = 'Indian';
	public function label(): string
	{
		return match ($this) {
			self::African => 'African',
			self::Black => 'Black',
			self::White => 'White',
			self::Colored => 'Colored',
			self::Indian => 'Indian',
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

