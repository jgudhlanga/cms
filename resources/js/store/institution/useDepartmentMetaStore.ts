import { defineStore } from 'pinia';

export type IDepartmentTabsStore = {
    activeTab: string;
};

export const useDepartmentMetaStore = defineStore('department-meta-store', {
    state: (): IDepartmentTabsStore => {
        return {
            activeTab: 'about_us',
        };
    },
    persist: true,
});
