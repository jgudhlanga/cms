import { defineStore } from 'pinia';

export type IHmsTabsStore = {
    activeTab: string;
    hostelRefreshKey: number;
};

export const useHmsStore = defineStore('hms-store', {
    state: (): IHmsTabsStore => {
        return {
            activeTab: 'hostels',
            hostelRefreshKey: 0,
        };
    },
    actions: {
        refreshHostels() {
            this.hostelRefreshKey++;
        },
    },
    persist: {
        pick: ['activeTab'],
    },
});
