import type { DepartmentLevel } from '@/types/department-meta-data';
import type { SelectOption } from '@/types/utils';

export function normalizeLevelName(name?: string | null): string {
    return name?.trim().toLowerCase() ?? '';
}

export function toDepartmentLevelSelectOption(item: DepartmentLevel): SelectOption {
    return {
        value: Number(item.id?.toString() ?? ''),
        label: item?.attributes?.level,
        relationshipOneValue: String(item.attributes.levelId),
    };
}

export function filterDepartmentLevelOptions(
    levels: DepartmentLevel[],
    selectedLevelName: string | null | undefined,
    restrictToSelectedLevel: boolean,
): SelectOption[] {
    const selectedName = normalizeLevelName(selectedLevelName);

    return levels
        .filter((item) => {
            if (!restrictToSelectedLevel || !selectedName) {
                return true;
            }

            return normalizeLevelName(item.attributes.level) === selectedName;
        })
        .map(toDepartmentLevelSelectOption);
}

export function findMatchingDepartmentLevelOption(
    levels: DepartmentLevel[],
    selectedLevelName: string | null | undefined,
): SelectOption | null {
    const selectedName = normalizeLevelName(selectedLevelName);
    if (!selectedName) {
        return null;
    }

    const match = levels.find((item) => normalizeLevelName(item.attributes.level) === selectedName);

    return match ? toDepartmentLevelSelectOption(match) : null;
}
