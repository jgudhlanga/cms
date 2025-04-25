import { LucideIcon } from 'lucide-vue-next';
import { Component } from 'vue';

export interface BreadcrumbItemInterface {
	title?: any;
	href?: any;
	transChoiceKey?: any;
	transKey?: any;
}

export interface MenuItemInterface {
	groupLabel?: string;
	title?: string;
	transChoiceKey?: string;
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
