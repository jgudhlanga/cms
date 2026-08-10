import { PAGINATION_ITEMS_PER_PAGE, PAGINATION_MAX_LIMIT } from '@/lib/constants';
import { paginateLocally, parsePaginationFromUrl } from '@/lib/local-pagination';
import { describe, expect, it } from 'vitest';

describe('paginateLocally', () => {
    const rows = Array.from({ length: 37 }, (_, index) => ({ id: index + 1 }));
    const baseUrl = '/institution/fee-structures';

    it('returns the first page with Laravel-shaped meta and links', () => {
        const result = paginateLocally(rows, baseUrl, 1, 15);

        expect(result.data).toHaveLength(15);
        expect(result.data[0]).toEqual({ id: 1 });
        expect(result.data[14]).toEqual({ id: 15 });
        expect(result.meta).toMatchObject({
            current_page: 1,
            last_page: 3,
            per_page: 15,
            total: 37,
            from: 1,
            to: 15,
            path: '/institution/fee-structures',
        });
        expect(result.links.prev).toBeNull();
        expect(result.links.next).toBe('/institution/fee-structures?page=2&page_size=15');
        expect(result.links.first).toBe('/institution/fee-structures?page=1&page_size=15');
        expect(result.links.last).toBe('/institution/fee-structures?page=3&page_size=15');
    });

    it('returns the middle and last pages correctly', () => {
        const page2 = paginateLocally(rows, baseUrl, 2, 15);
        expect(page2.data.map((row) => row.id)).toEqual(Array.from({ length: 15 }, (_, i) => i + 16));
        expect(page2.meta.from).toBe(16);
        expect(page2.meta.to).toBe(30);
        expect(page2.links.prev).toBe('/institution/fee-structures?page=1&page_size=15');
        expect(page2.links.next).toBe('/institution/fee-structures?page=3&page_size=15');

        const page3 = paginateLocally(rows, baseUrl, 3, 15);
        expect(page3.data.map((row) => row.id)).toEqual([31, 32, 33, 34, 35, 36, 37]);
        expect(page3.meta.from).toBe(31);
        expect(page3.meta.to).toBe(37);
        expect(page3.links.next).toBeNull();
    });

    it('clamps out-of-range pages and normalizes invalid page sizes', () => {
        const clamped = paginateLocally(rows, baseUrl, 99, 15);
        expect(clamped.meta.current_page).toBe(3);
        expect(clamped.data).toHaveLength(7);

        const normalized = paginateLocally(rows, baseUrl, 1, 0);
        expect(normalized.meta.per_page).toBe(PAGINATION_ITEMS_PER_PAGE);

        const all = paginateLocally(rows, baseUrl, 1, PAGINATION_MAX_LIMIT);
        expect(all.data).toHaveLength(37);
        expect(all.meta.last_page).toBe(1);
        expect(all.links.next).toBeNull();
    });

    it('handles an empty list', () => {
        const empty = paginateLocally([], baseUrl, 1, 15);
        expect(empty.data).toEqual([]);
        expect(empty.meta).toMatchObject({
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
        });
    });
});

describe('parsePaginationFromUrl', () => {
    it('reads page and page_size from the query string', () => {
        expect(parsePaginationFromUrl('/institution/fee-structures?page=2&page_size=20')).toEqual({
            page: 2,
            pageSize: 20,
        });
    });

    it('falls back to defaults when params are missing or invalid', () => {
        expect(parsePaginationFromUrl('/institution/fee-structures', { page: 3, pageSize: 10 })).toEqual({
            page: 3,
            pageSize: 10,
        });
        expect(parsePaginationFromUrl('/institution/fee-structures?page=abc&page_size=-5')).toEqual({
            page: 1,
            pageSize: PAGINATION_ITEMS_PER_PAGE,
        });
    });
});
