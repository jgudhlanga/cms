import { defineStore } from 'pinia';

export type IClassListStore = {
    classList: Record<string, boolean>;
};

export const useClassListStore = defineStore('class-lists', {
    state: (): IClassListStore => {
        return {
            classList: {},
        };
    },
    persist: true,
});
