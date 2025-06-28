<?php

namespace App\Enums\Shared;

enum PaymentMethodEnum: string
{

	case CREDIT_CARD = 'Credit Card';
	case CASH_PAYMENT = 'Cash Payment';
	case DEBIT_ORDER = 'Debit Order';
	case EFT = 'EFT';
	case STOP_ORDER = 'Stop Order';

	public function label(): string
	{
		return match ($this) {
			self::CREDIT_CARD => 'Credit Card',
			self::CASH_PAYMENT => 'Cash Payment',
			self::DEBIT_ORDER => 'Debit Order',
			self::EFT => 'EFT',
			self::STOP_ORDER => 'Stop Order',
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

