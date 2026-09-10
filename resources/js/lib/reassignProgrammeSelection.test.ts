import { describe, expect, it } from 'vitest';
import {
    applicationIdsForSubmit,
    pruneSelectionToVisible,
} from '@/lib/reassignProgrammeSelection';

describe('reassignProgrammeSelection', () => {
    it('prunes hidden ids when a mode filter is active', () => {
        expect(pruneSelectionToVisible([1, 2, 3, 4], [2, 3])).toEqual([2, 3]);
    });

    it('returns an empty selection when nothing is visible', () => {
        expect(pruneSelectionToVisible([1, 2], [])).toEqual([]);
    });

    it('submits the full selection when no mode filter is active', () => {
        expect(applicationIdsForSubmit([1, 2, 3], [1], [])).toEqual([1, 2, 3]);
    });

    it('submits only visible ids when a mode filter is active', () => {
        expect(applicationIdsForSubmit([1, 2, 3, 4], [2, 4], [9])).toEqual([2, 4]);
    });
});
