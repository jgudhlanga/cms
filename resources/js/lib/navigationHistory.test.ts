import { describe, expect, it } from 'vitest';

import { resolvePreviousPage } from '@/lib/navigationHistory';

const DASHBOARD = '/dashboard';
const GATEWAY = 'https://cms.test/integrations/payment-gateway';

describe('resolvePreviousPage', () => {
    it('returns the page the visit came from', () => {
        expect(resolvePreviousPage('/institution', GATEWAY, DASHBOARD, GATEWAY)).toBe('/institution');
    });

    it('keeps the query string of the page being returned to', () => {
        expect(resolvePreviousPage('/students?page=3', GATEWAY, DASHBOARD, GATEWAY)).toBe('/students?page=3');
    });

    it('falls back to the dashboard when there is no trail', () => {
        expect(resolvePreviousPage(null, GATEWAY, DASHBOARD, GATEWAY)).toBe(DASHBOARD);
    });

    it('falls back rather than returning to the page being left', () => {
        expect(resolvePreviousPage('/integrations/payment-gateway', GATEWAY, DASHBOARD, GATEWAY)).toBe(DASHBOARD);
        expect(resolvePreviousPage(GATEWAY, '/integrations/payment-gateway', DASHBOARD)).toBe(DASHBOARD);
    });
});
