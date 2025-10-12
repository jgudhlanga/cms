import { defineStore } from 'pinia';

export type IStudentTabsStore = {
    activeTab: string;
};

export const useStudentTabsStore = defineStore('student-tabs', {
    state: (): IStudentTabsStore => {
        return {
            activeTab: 'personal',
        };
    },
    persist: true,
});
