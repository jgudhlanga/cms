import { describe, expect, it } from 'vitest';

import { ColorVariant } from '@/enums/colors';
import { TypeVariant } from '@/enums/type-variants';
import { applicationStatusAlertType, applicationStatusVariant } from '@/lib/applicationStatusPresentation';

describe('applicationStatusVariant', () => {
    it('maps workflow steps to badge colors', () => {
        expect(applicationStatusVariant('Review')).toBe(ColorVariant.info);
        expect(applicationStatusVariant('Requirements')).toBe(ColorVariant.warning);
        expect(applicationStatusVariant('Waitlisted')).toBe(ColorVariant.warning);
        expect(applicationStatusVariant('Accepted')).toBe(ColorVariant.success);
        expect(applicationStatusVariant('Enrolled')).toBe(ColorVariant.success);
        expect(applicationStatusVariant('Rejected')).toBe(ColorVariant.danger);
        expect(applicationStatusVariant('Unsuccessful')).toBe(ColorVariant.danger);
    });

    it('defaults unknown steps to shade', () => {
        expect(applicationStatusVariant('')).toBe(ColorVariant.shade);
        expect(applicationStatusVariant('Unknown')).toBe(ColorVariant.shade);
    });
});

describe('applicationStatusAlertType', () => {
    it('maps workflow steps to alert types', () => {
        expect(applicationStatusAlertType('Review')).toBe(TypeVariant.info);
        expect(applicationStatusAlertType('Requirements')).toBe(TypeVariant.warning);
        expect(applicationStatusAlertType('Waitlisted')).toBe(TypeVariant.warning);
        expect(applicationStatusAlertType('Accepted')).toBe(TypeVariant.success);
        expect(applicationStatusAlertType('Enrolled')).toBe(TypeVariant.success);
        expect(applicationStatusAlertType('Rejected')).toBe(TypeVariant.danger);
        expect(applicationStatusAlertType('Unsuccessful')).toBe(TypeVariant.danger);
    });

    it('defaults unknown steps to info', () => {
        expect(applicationStatusAlertType('')).toBe(TypeVariant.info);
        expect(applicationStatusAlertType('Unknown')).toBe(TypeVariant.info);
    });
});
