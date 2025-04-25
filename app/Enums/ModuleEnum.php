<?php

namespace App\Enums;

enum ModuleEnum: string
{

	case ACL = 'Acl';
	case COMMUNICATIONS = 'Communications';
	case DASHBOARDS = 'Dashboards';
	case REPORTS = 'Reports';
	case ROOT = 'Root';
	case SETTINGS = 'Settings';
	case TENANTS = 'Tenants';
	case USERS = 'Users';
	case SHARED = 'Shared';
	case OTHER = 'Other';

	public function label(): string
	{
		return match ($this) {
			self::ACL => 'Acl',
			self::COMMUNICATIONS => 'Communications',
			self::DASHBOARDS => 'Dashboards',
			self::REPORTS => 'Reports',
			self::ROOT => 'Root',
			self::SETTINGS => 'Settings',
			self::TENANTS => 'Tenants',
			self::USERS => 'Users',
			self::SHARED => 'Shared',
			self::OTHER => 'Other',
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

