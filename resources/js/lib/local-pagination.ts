import { PAGINATION_ITEMS_PER_PAGE, PAGINATION_MAX_LIMIT } from '@/lib/constants';
import { buildPaginationPageLinks } from '@/lib/json-api';
import type { DataListProps } from '@/types/data-pagination';

const origin = (): string => (typeof window !== 'undefined' ? window.location.origin : 'https://localhost');

const pageUrl = (baseUrl: string, page: number, pageSize: number): string => {
    const parsed = new URL(baseUrl, origin());
    parsed.searchParams.set('page', String(page));
    parsed.searchParams.set('page_size', String(pageSize));

    return `${parsed.pathname}${parsed.search}`;
};

const normalizePageSize = (pageSize: number): number => {
    if (!Number.isFinite(pageSize) || pageSize < 1) {
        return PAGINATION_ITEMS_PER_PAGE;
    }

    return Math.min(Math.floor(pageSize), PAGINATION_MAX_LIMIT);
};

export const parsePaginationFromUrl = (
    url: string,
    defaults: { page?: number; pageSize?: number } = {},
): { page: number; pageSize: number } => {
    const parsed = new URL(url, origin());
    const pageParam = Number(parsed.searchParams.get('page'));
    const pageSizeParam = Number(parsed.searchParams.get('page_size'));

    return {
        page: Number.isFinite(pageParam) && pageParam > 0 ? Math.floor(pageParam) : (defaults.page ?? 1),
        pageSize: normalizePageSize(
            Number.isFinite(pageSizeParam) && pageSizeParam > 0
                ? pageSizeParam
                : (defaults.pageSize ?? PAGINATION_ITEMS_PER_PAGE),
        ),
    };
};

export const paginateLocally = <T>(
    rows: T[],
    baseUrl: string,
    page = 1,
    pageSize = PAGINATION_ITEMS_PER_PAGE,
): DataListProps<T> => {
    const perPage = normalizePageSize(pageSize);
    const total = rows.length;
    const lastPage = Math.max(1, Math.ceil(total / perPage) || 1);
    const currentPage = Math.min(Math.max(1, Math.floor(page) || 1), lastPage);
    const start = (currentPage - 1) * perPage;
    const data = rows.slice(start, start + perPage);
    const path = new URL(baseUrl, origin()).pathname;

    return {
        data,
        meta: {
            current_page: currentPage,
            last_page: lastPage,
            per_page: perPage,
            total,
            from: total > 0 ? start + 1 : 0,
            to: total > 0 ? start + data.length : 0,
            path,
            links: buildPaginationPageLinks(currentPage, lastPage),
        },
        links: {
            first: pageUrl(baseUrl, 1, perPage),
            last: pageUrl(baseUrl, lastPage, perPage),
            prev: currentPage > 1 ? pageUrl(baseUrl, currentPage - 1, perPage) : null,
            next: currentPage < lastPage ? pageUrl(baseUrl, currentPage + 1, perPage) : null,
        },
    };
};
