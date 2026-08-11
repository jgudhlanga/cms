import { describe, expect, it } from 'vitest';
import {
    activityEventKind,
    activityPropertyEntries,
    activitySubjectLabel,
} from '@/lib/activityTimeline';

describe('activityTimeline helpers', () => {
    it('maps description to event kind', () => {
        expect(activityEventKind('created')).toBe('created');
        expect(activityEventKind('Updated')).toBe('updated');
        expect(activityEventKind('deleted')).toBe('other');
        expect(activityEventKind(null)).toBe('other');
    });

    it('returns basename from subject type', () => {
        expect(activitySubjectLabel('App\\Models\\Users\\User')).toBe('User');
        expect(activitySubjectLabel('StudentExamResult')).toBe('StudentExamResult');
        expect(activitySubjectLabel('')).toBe('');
    });

    it('filters sensitive keys and formats property entries', () => {
        expect(
            activityPropertyEntries({
                login_count: 900,
                password: 'secret',
                remember_token: 'token',
                nested: { a: 1 },
            }),
        ).toEqual([
            { key: 'login_count', value: '900' },
            { key: 'nested', value: '{"a":1}' },
        ]);

        expect(activityPropertyEntries(null)).toEqual([]);
    });
});
