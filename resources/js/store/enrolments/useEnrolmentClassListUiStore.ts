import { defineStore } from 'pinia';

type EnrolmentUiState = {
    /** Context key → open gender accordion value (`disabled` | `females` | `males` | ''). */
    openGenderGroupByContext: Record<string, string>;
    /** `${context}:${group}` → open status section keys. */
    openStatusSectionsByGroup: Record<string, Record<string, boolean>>;
};

export const useEnrolmentClassListUiStore = defineStore('enrolment-class-list-ui', {
    state: (): EnrolmentUiState => ({
        openGenderGroupByContext: {},
        openStatusSectionsByGroup: {},
    }),
    actions: {
        contextKey(parts: {
            departmentId: string | number;
            levelId: string | number;
            courseId?: string | number | null;
            intakePeriodId: string | number;
            modeOfStudyId: string | number;
        }): string {
            return [
                parts.departmentId,
                parts.levelId,
                parts.courseId ?? '0',
                parts.intakePeriodId,
                parts.modeOfStudyId,
            ].join(':');
        },

        groupKey(contextKey: string, group: string): string {
            return `${contextKey}:${group}`;
        },

        getOpenGenderGroup(contextKey: string, fallback = ''): string {
            return this.openGenderGroupByContext[contextKey] ?? fallback;
        },

        setOpenGenderGroup(contextKey: string, group: string): void {
            this.openGenderGroupByContext[contextKey] = group;
        },

        getOpenStatusSections(contextKey: string, group: string): Record<string, boolean> {
            return this.openStatusSectionsByGroup[this.groupKey(contextKey, group)] ?? {};
        },

        setOpenStatusSections(contextKey: string, group: string, sections: Record<string, boolean>): void {
            this.openStatusSectionsByGroup[this.groupKey(contextKey, group)] = { ...sections };
        },

        toggleStatusSection(contextKey: string, group: string, sectionKey: string): void {
            const current = { ...this.getOpenStatusSections(contextKey, group) };
            current[sectionKey] = !current[sectionKey];
            this.setOpenStatusSections(contextKey, group, current);
        },
    },
    persist: true,
});
