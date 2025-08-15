import { defineStore } from 'pinia';

export type IUserTabsStore = {
    activeTab: string;
};

export const useUserTabsStore = defineStore('user-tabs', {
    state: (): IUserTabsStore => {
        return {
            activeTab: 'basic_info',
        };
    },
    persist: true,
});
