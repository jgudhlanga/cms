import { defineStore } from 'pinia';

export const useConsoleStore = defineStore('console', {
    state: () => {
        return {
            search: '',
        };
    },
    actions: {
        clearSearch() {
            this.search = '';
        },
    },
});
