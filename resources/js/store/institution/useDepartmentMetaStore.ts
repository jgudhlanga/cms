import { defineStore } from 'pinia';

export type IDepartmentTabsStore = {
    activeTab: string;
    academicClassConfigsRefreshNonce: number;
};

export const useDepartmentMetaStore = defineStore('department-meta-store', {
    state: (): IDepartmentTabsStore => {
        return {
            activeTab: 'enrolments',
            academicClassConfigsRefreshNonce: 0,
        };
    },
    actions: {
        bumpAcademicClassConfigsRefresh(): void {
            this.academicClassConfigsRefreshNonce += 1;
        },
    },
    persist: {
        pick: ['activeTab'],
    },
});
