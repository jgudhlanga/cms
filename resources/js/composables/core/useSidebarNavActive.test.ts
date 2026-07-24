import { beforeEach, describe, expect, it, vi } from 'vitest';

const pageState = vi.hoisted(() => ({
    url: '/hms/hostels',
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageState,
}));

import { useSidebarNavActive } from '@/composables/core/useSidebarNavActive';

describe('useSidebarNavActive', () => {
    beforeEach(() => {
        pageState.url = '/hms/hostels';
    });

    it('matches exact paths', () => {
        pageState.url = '/settings';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/settings')).toBe(true);
        expect(isActive('/users')).toBe(false);
    });

    it('matches nested paths when href has no query', () => {
        pageState.url = '/institution/config';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/institution')).toBe(true);
        expect(isActive('/institution-departments')).toBe(false);
    });

    it('distinguishes tab query siblings', () => {
        pageState.url = '/hms/hostels?tab=applications';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/hms/hostels?tab=applications')).toBe(true);
        expect(isActive('/hms/hostels?tab=hostels')).toBe(false);
        expect(isActive('/hms/hostels')).toBe(true);
    });

    it('distinguishes is_academic query siblings', () => {
        pageState.url = '/institution-departments?is_academic=1';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/institution-departments?is_academic=1')).toBe(true);
        expect(isActive('/institution-departments?is_academic=0')).toBe(false);
    });

    it('does not activate query-param siblings on nested department routes', () => {
        pageState.url = '/institution-departments/123';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/institution-departments?is_academic=0')).toBe(false);
        expect(isActive('/institution-departments?is_academic=1')).toBe(false);
    });

    it('does not activate tab query siblings on nested hostel routes', () => {
        pageState.url = '/hms/hostels/5';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/hms/hostels?tab=hostels')).toBe(false);
        expect(isActive('/hms/hostels?tab=applications')).toBe(false);
    });

    it('does not activate /settings parent for /rbac nested paths', () => {
        pageState.url = '/rbac/roles';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/rbac')).toBe(true);
        expect(isActive('/settings')).toBe(false);
    });

    it('does not activate /rbac parent for /settings nested paths', () => {
        pageState.url = '/settings/countries';
        const { isActive } = useSidebarNavActive();
        expect(isActive('/settings')).toBe(true);
        expect(isActive('/rbac')).toBe(false);
    });
});
