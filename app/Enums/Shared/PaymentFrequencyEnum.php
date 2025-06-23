<?php

namespace App\Enums\Shared;

enum PaymentFrequencyEnum: string
{

	case MONTHLY = 'Monthly';
	case ANNUALLY = 'Annually';
	case ONCE_OFF = 'Once off';

	public function label(): string
	{
		return match ($this) {
			self::MONTHLY => 'Monthly',
			self::ANNUALLY => 'Annually',
			self::ONCE_OFF => 'Once off',
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

