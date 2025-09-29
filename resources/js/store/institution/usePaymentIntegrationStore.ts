import { defineStore } from 'pinia';

export const usePaymentIntegrationStore = defineStore('payment-search', {
    state: () => {
        return {
            search: '',
            reload: false
        };
    },
    actions: {
        clearSearch() {
            this.search = '';
            this.reload = false;
        },
    },
    persist: true,
});
