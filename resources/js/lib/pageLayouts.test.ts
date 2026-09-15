import { describe, expect, it } from 'vitest';

import { layoutNameForPage } from '@/lib/pageLayouts';

describe('layoutNameForPage', () => {
    it('wraps auth pages in the guest layout', () => {
        expect(layoutNameForPage('auth/Login')).toBe('guest');
    });

    it('uses the registration layout for guest and registration portal pages', () => {
        expect(layoutNameForPage('portal/guest/RegistrationUserForm')).toBe('portal-registration');
        expect(layoutNameForPage('portal/registration/Maintenance')).toBe('portal-registration');
    });

    it('uses the plain layout for site, application and integration pages', () => {
        expect(layoutNameForPage('site/IdCardVerify')).toBe('plain');
        expect(layoutNameForPage('portal/application/CreateApplication')).toBe('plain');
        expect(layoutNameForPage('integrations/payments/Feedback')).toBe('plain');
    });

    it('uses the app layout for everything else, including other portal pages', () => {
        expect(layoutNameForPage('dashboard/Index')).toBe('app');
        expect(layoutNameForPage('portal/student/Index')).toBe('app');
    });
});
