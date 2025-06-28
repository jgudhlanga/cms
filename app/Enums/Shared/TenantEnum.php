<?php

namespace App\Enums\Shared;

enum TenantEnum: string
{
	case HARARE_POLY = 'Harare Poly';
    case PENSTEJ_SYSTEMS = 'Penstej Systems';
	// extra helper to allow for greater customization of displayed values, without disclosing the name/value data directly
	public function label(): string
	{
		return match ($this) {
			self::HARARE_POLY => 'Harare Poly',
            self::PENSTEJ_SYSTEMS => 'Penstej Systems',
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
