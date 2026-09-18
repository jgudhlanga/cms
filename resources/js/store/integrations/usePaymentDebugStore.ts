import { defineStore } from 'pinia';

export const usePaymentDebugStore = defineStore('payment-debug', {
    state: () => {
        return {
            search: '',
            reload: false,
        };
    },
    actions: {
        clearSearch() {
            this.search = '';
            this.reload = false;
        },
    },
});
