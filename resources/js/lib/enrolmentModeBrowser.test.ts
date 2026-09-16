import { describe, expect, it } from 'vitest';

import { buildOrderedModes, pickPreferredMode, sortModesOfStudy } from '@/lib/enrolmentModeBrowser';
import { ModeOfStudy } from '@/types/institution';

const mode = (id: number, name: string): ModeOfStudy => ({
    type: 'mode-of-study',
    id: String(id),
    attributes: { name },
});

const ojet = mode(4, 'Ojet');
const fullTime = mode(1, 'Full Time');
const partTime = mode(2, 'Part Time');

describe('buildOrderedModes', () => {
    it('includes a mode that has applications but is not configured for the department', () => {
        const modes = buildOrderedModes(
            [ojet],
            [
                { modeOfStudyId: 1, modeOfStudyName: 'Full Time', count: 25 },
                { modeOfStudyId: 4, modeOfStudyName: 'Ojet', count: 0 },
            ],
        );

        expect(modes.map((row) => row.attributes.name)).toEqual(['Full Time', 'Ojet']);
    });

    it('keeps a configured mode that has no applications', () => {
        const modes = buildOrderedModes([ojet], []);

        expect(modes.map((row) => row.id)).toEqual(['4']);
    });

    it('does not duplicate a mode that is both configured and counted', () => {
        const modes = buildOrderedModes([fullTime], [{ modeOfStudyId: 1, modeOfStudyName: 'Full Time', count: 3 }]);

        expect(modes).toHaveLength(1);
    });

    it('ignores totals with no applications or no mode', () => {
        const modes = buildOrderedModes(
            [ojet],
            [
                { modeOfStudyId: 2, modeOfStudyName: 'Part Time', count: 0 },
                { modeOfStudyId: 0, count: 7 },
            ],
        );

        expect(modes.map((row) => row.id)).toEqual(['4']);
    });

    it('falls back to the mode id when the name is missing', () => {
        const modes = buildOrderedModes([], [{ modeOfStudyId: 9, count: 2 }]);

        expect(modes[0].attributes.name).toBe('#9');
    });

    it('orders modes the same way as the configured list', () => {
        expect(sortModesOfStudy([ojet, partTime, fullTime]).map((row) => row.attributes.name)).toEqual(['Full Time', 'Part Time', 'Ojet']);

        expect(buildOrderedModes([ojet, partTime, fullTime], []).map((row) => row.attributes.name)).toEqual(['Full Time', 'Part Time', 'Ojet']);
    });
});

describe('pickPreferredMode', () => {
    it('prefers the first mode that actually has applications', () => {
        const modes = [fullTime, ojet];
        const totals = [
            { modeOfStudyId: 4, modeOfStudyName: 'Ojet', count: 0 },
            { modeOfStudyId: 1, modeOfStudyName: 'Full Time', count: 25 },
        ];

        expect(pickPreferredMode(modes, null, totals)).toBe('1');
    });

    it('respects an explicitly requested mode even when it is empty', () => {
        const modes = [fullTime, ojet];
        const totals = [{ modeOfStudyId: 1, modeOfStudyName: 'Full Time', count: 25 }];

        expect(pickPreferredMode(modes, '4', totals)).toBe('4');
    });

    it('ignores a requested mode that is not in the list', () => {
        expect(pickPreferredMode([fullTime], '99', [{ modeOfStudyId: 1, count: 4 }])).toBe('1');
    });

    it('falls back to the first mode when nothing has applications', () => {
        expect(pickPreferredMode([ojet, partTime], null, [])).toBe('4');
    });

    it('returns an empty id when there are no modes', () => {
        expect(pickPreferredMode([], '1', [])).toBe('');
    });
});
