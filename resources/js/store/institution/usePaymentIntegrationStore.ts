import { defineStore } from 'pinia';

export const usePaymentIntegrationStore = defineStore('payment-search', {
    state: () => {
        return {
            search: '',
            reload: false,
            selectedLedgerableType: '' as string,
        };
    },
    actions: {
        clearSearch() {
            this.search = '';
            this.reload = false;
            this.selectedLedgerableType = '';
        },
    },
    persist: true,
});
