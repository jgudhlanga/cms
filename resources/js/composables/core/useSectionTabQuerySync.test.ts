import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick, ref } from 'vue';

const pageState = vi.hoisted(() => ({
    url: '/institution/departments/14',
}));

const routerMock = vi.hoisted(() => ({
    get: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => pageState,
    router: routerMock,
}));

import { useSectionTabQuerySync } from '@/composables/core/useSectionTabQuerySync';

describe('useSectionTabQuerySync', () => {
    const replaceState = vi.fn();

    beforeEach(() => {
        pageState.url = '/institution/departments/14';
        routerMock.get.mockClear();
        replaceState.mockClear();
        vi.stubGlobal('window', {
            location: {
                origin: 'http://localhost',
                href: 'http://localhost/institution/departments/14',
            },
            history: {
                state: {},
                replaceState,
            },
        });
    });

    it('does not start an Inertia visit during setup', () => {
        const activeTab = ref('enrolments');

        useSectionTabQuerySync(activeTab, () => ['enrolments', 'staff', 'setup']);

        expect(routerMock.get).not.toHaveBeenCalled();
        expect(activeTab.value).toBe('enrolments');
    });

    it('applies a valid tab from the current page URL', () => {
        pageState.url = '/institution/departments/14?tab=staff';
        const activeTab = ref('enrolments');

        useSectionTabQuerySync(activeTab, () => ['enrolments', 'staff', 'setup']);

        expect(activeTab.value).toBe('staff');
        expect(routerMock.get).not.toHaveBeenCalled();
    });

    it('writes the tab into the URL without dropping other query params', async () => {
        pageState.url = '/institution/departments/14?intake_period_id=7';
        const activeTab = ref('enrolments');

        useSectionTabQuerySync(activeTab, () => ['enrolments', 'staff', 'setup']);

        activeTab.value = 'staff';
        await nextTick();

        expect(routerMock.get).not.toHaveBeenCalled();
        expect(replaceState).toHaveBeenCalledWith(
            expect.objectContaining({
                url: '/institution/departments/14?intake_period_id=7&tab=staff',
            }),
            '',
            '/institution/departments/14?intake_period_id=7&tab=staff',
        );
    });
});
