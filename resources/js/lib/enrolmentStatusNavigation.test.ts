import { describe, expect, it } from 'vitest';

import { canOpenEnrolmentStatusList, enrolmentStatusListPermission } from '@/lib/enrolmentStatusNavigation';

describe('enrolmentStatusListPermission', () => {
    it('maps each class-list status to its navigation permission', () => {
        expect(enrolmentStatusListPermission('provisional')).toBe('verify:class-lists');
        expect(enrolmentStatusListPermission('waiting')).toBeNull();
        expect(enrolmentStatusListPermission('verified')).toBe('confirm:class-lists');
        expect(enrolmentStatusListPermission('final')).toBe('manage-final:class-lists');
        expect(enrolmentStatusListPermission('failed')).toBeNull();
    });
});

describe('canOpenEnrolmentStatusList', () => {
    it('opens only the status the user is permitted to work', () => {
        expect(canOpenEnrolmentStatusList('provisional', true, 12, ['verify:class-lists'])).toBe(true);
        expect(canOpenEnrolmentStatusList('waiting', true, 12, ['verify:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('verified', true, 12, ['confirm:class-lists'])).toBe(true);
        expect(canOpenEnrolmentStatusList('final', true, 12, ['manage-final:class-lists'])).toBe(true);
    });

    it('does not treat application view permissions as class-list navigation', () => {
        const viewOnly = ['view:student-applications', 'viewAny:student-applications'];

        expect(canOpenEnrolmentStatusList('provisional', true, 12, viewOnly)).toBe(false);
        expect(canOpenEnrolmentStatusList('verified', true, 12, viewOnly)).toBe(false);
        expect(canOpenEnrolmentStatusList('final', true, 12, viewOnly)).toBe(false);
        expect(canOpenEnrolmentStatusList('provisional', true, 12, ['view:class-lists'])).toBe(false);
    });

    it('does not cross-link statuses or navigate without a department', () => {
        expect(canOpenEnrolmentStatusList('provisional', true, 12, ['confirm:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('verified', true, 12, ['verify:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('final', true, 12, ['confirm:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('failed', true, 12, ['verify:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('waiting', true, 12, ['verify:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('provisional', false, 12, ['verify:class-lists'])).toBe(false);
        expect(canOpenEnrolmentStatusList('provisional', true, 0, ['verify:class-lists'])).toBe(false);
    });
});
