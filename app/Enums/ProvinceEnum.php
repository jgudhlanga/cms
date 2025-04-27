<?php

namespace App\Enums;

enum ProvinceEnum: string
{
    case BULAWAYO = 'Bulawayo';
	case HARARE = 'Harare';
	case MANICALAND = 'Manicaland';
	case MASHONALAND_CENTRAL = 'Mashonaland Central';
	case MASHONALAND_EAST = 'Mashonaland East';
	case MASHONALAND_WEST = 'Mashonaland West';
	case MASVINGO = 'Masvingo';
	case MATEBELELAND_NORTH = 'Matebeleland North';
	case MATEBELELAND_SOUTH = 'Matebeleland South';
	case MIDLANDS = 'Midlands';
	case UNKNOWN_PROVINCE = 'Unknown Province';

	public function label(): string
	{
		return match ($this) {
			self::BULAWAYO => 'Bulawayo',
            self::HARARE => 'Harare',
            self::MANICALAND => 'Manicaland',
            self::MASHONALAND_CENTRAL => 'Mashonaland Central',
            self::MASHONALAND_EAST => 'Mashonaland East',
            self::MASHONALAND_WEST => 'Mashonaland West',
            self::MASVINGO => 'Masvingo',
            self::MATEBELELAND_NORTH => 'Matebeleland North',
            self::MATEBELELAND_SOUTH => 'Matebeleland South',
            self::MIDLANDS => 'Midlands',
            self::UNKNOWN_PROVINCE => 'Unknown Province',
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

