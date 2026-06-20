import { defineStore } from 'pinia';

export type IDashboardTabsStore = {
    activeTab: string;
};

export const useDashboardStore = defineStore('dashboard-store', {
    state: (): IDashboardTabsStore => {
        return {
            activeTab: 'overview',
        };
    },
    persist: {
        pick: ['activeTab'],
    },
});
