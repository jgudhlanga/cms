import { describe, expect, it } from 'vitest';

import {
    filterDepartmentLevelOptions,
    findMatchingDepartmentLevelOption,
} from '@/lib/departmentLevelComboOptions';
import type { DepartmentLevel } from '@/types/department-meta-data';

function makeDepartmentLevel(id: number, levelName: string, levelId = id): DepartmentLevel {
    return {
        id,
        type: 'department-level',
        attributes: {
            level: levelName,
            levelId,
            showOnCurrentApplicationPeriod: true,
        },
    } as DepartmentLevel;
}

describe('filterDepartmentLevelOptions', () => {
    const levels = [
        makeDepartmentLevel(1, 'ND', 10),
        makeDepartmentLevel(2, 'HND', 20),
        makeDepartmentLevel(3, 'NC', 30),
    ];

    it('returns only the selected level name when restriction is enabled', () => {
        const options = filterDepartmentLevelOptions(levels, 'ND', true);

        expect(options).toHaveLength(1);
        expect(options[0].label).toBe('ND');
    });

    it('returns all department levels when restriction is disabled', () => {
        const options = filterDepartmentLevelOptions(levels, 'ND', false);

        expect(options).toHaveLength(3);
        expect(options.map((option) => option.label)).toEqual(['ND', 'HND', 'NC']);
    });
});

describe('findMatchingDepartmentLevelOption', () => {
    const levels = [
        makeDepartmentLevel(1, 'ND', 10),
        makeDepartmentLevel(2, 'HND', 20),
    ];

    it('finds the option for the selected level name', () => {
        const option = findMatchingDepartmentLevelOption(levels, 'HND');

        expect(option).toEqual({
            value: 2,
            label: 'HND',
            relationshipOneValue: '20',
        });
    });

    it('returns null when unrestricted selection should remain manual', () => {
        expect(findMatchingDepartmentLevelOption(levels, null)).toBeNull();
    });
});
