import { defineStore } from 'pinia';

export type IDepartmentTabsStore = {
    activeTab: string;
};

export const useDepartmentMetaStore = defineStore('department-meta-store', {
    state: (): IDepartmentTabsStore => {
        return {
            activeTab: 'enrolments',
        };
    },
    persist: true,
});
