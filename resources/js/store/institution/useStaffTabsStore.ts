import { defineStore } from 'pinia';

export type IStaffTabsStore = {
    activeTab: string;
};

export const useStaffTabsStore = defineStore('staff-tabs', {
    state: (): IStaffTabsStore => {
        return {
            activeTab: 'basic_info',
        };
    },
    persist: true,
});
