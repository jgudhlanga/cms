import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';

export type TableActionButton = {
	isArchived?: boolean;
	hasView?: boolean;
	onEdit?: () => void | null;
	onView?: () => void | null;
	onArchive?: () => void | null;
	onRestore?: () => void | null;
	onDelete?: () => void | null;
};

export type TableButton = {
	title: string;
	variant?: ColorVariant;
	classes?: string;
	onClick?: () => void | null;
};

export type TableActionOption = {
	key: string,
	action: () => void,
	title?: string;
	transChoiceKey?: string;
	transKey?: string;
	icon?: IconName,
};

export type ButtonDropdownOption = { key: string, action: () => void }
