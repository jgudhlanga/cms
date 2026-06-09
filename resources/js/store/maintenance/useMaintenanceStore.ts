import { defineStore } from 'pinia';

export type MaintenanceTabsStore = {
    activeTab: string;
};

export const useMaintenanceStore = defineStore('maintenance-store', {
    state: (): MaintenanceTabsStore => ({
        activeTab: 'users',
    }),
    persist: {
        pick: ['activeTab'],
    },
});
