import { User } from '@/types/users';

export interface DataListProps<T = any> {
	data: Array<T>;
	meta: PaginationMeta;
	links: PaginationRootLink,
}

export interface AuthObject {
	can: any,
	user: User,
}

export interface DataFilters {
	search: string | null;
	trashed: any;
}

export type PaginationMeta = {
	current_page: number | null,
	last_page: number | null,
	from: number | null,
	to: number | null,
	total: number | null,
	per_page: number | null,
	path: string | null,
	links: Array<PaginationLink> | null
}

export type PaginationRootLink = {
	first: string | null,
	last: string | null,
	prev: string | null,
	next: string | null
}

export type PaginationLink = {
	url: string | null
	label: string | number | null,
	active: boolean
}
export type ApiFilterResponse = {
    data?: Array<any> | [];
    meta?: PaginationMeta | null;
    links?: PaginationRootLink | null,
    filters?: DataFilters | null
    trashedCount?: any
}
