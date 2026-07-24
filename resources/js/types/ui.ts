import { LucideIcon } from 'lucide-vue-next';
import { Component } from 'vue';

export interface BreadcrumbItemInterface {
	title?: any;
	href?: any;
	transChoiceKey?: any;
	transChoiceKeyIndex?: number;
	transKey?: any;
}

export type MenuGroupKey =
	| 'overview'
	| 'students'
	| 'operations'
	| 'institution'
	| 'system'
	| 'lecturer'
	| 'portal';

export interface MenuGroupInterface {
	key: MenuGroupKey;
	items: Array<MenuItemInterface>;
	showSeparatorBefore?: boolean;
}

export interface MenuItemInterface {
	groupKey?: MenuGroupKey;
	groupLabel?: string;
	title?: string;
	transChoiceKey?: string;
	transChoiceKeyIndex?: number;
	transKey?: string;
	url?: string;
	icon?: Component;
	isActive?: boolean;
	show?: boolean;
	items?: Array<MenuItemInterface>;
}

export interface BreadcrumbItem {
	title: string;
	href: string;
}

export interface NavItem {
	title: string;
	href: string;
	icon?: LucideIcon;
	isActive?: boolean;
}

export type BreadcrumbItemType = BreadcrumbItem;

export type Link = {
	transChoiceKey?: string;
    transChoiceKeyIndex?: number;
	transKey?: string;
	url?: string;
	href?: string;
	title?: string;
	name?: any;
	params?: any;
	icon?: string;
	group?: string;
	children?: Array<Link>;
};
