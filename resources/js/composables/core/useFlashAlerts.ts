import { errorAlert, successAlert, warningAlert } from '@/lib/alerts';
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';

type FlashProps = {
    success?: string | null;
    error?: string | null;
    warning?: string | null;
};

export function useFlashAlerts(): void {
    const page = usePage();
    let lastSuccess: string | null = null;
    let lastError: string | null = null;
    let lastWarning: string | null = null;

    watch(
        () => page.props.flash as FlashProps | undefined,
        (flash) => {
            if (!flash) {
                return;
            }

            if (typeof flash.success === 'string' && flash.success.length > 0) {
                if (flash.success !== lastSuccess) {
                    lastSuccess = flash.success;
                    successAlert(flash.success);
                }
            } else {
                lastSuccess = null;
            }

            if (typeof flash.error === 'string' && flash.error.length > 0) {
                if (flash.error !== lastError) {
                    lastError = flash.error;
                    errorAlert(flash.error);
                }
            } else {
                lastError = null;
            }

            if (typeof flash.warning === 'string' && flash.warning.length > 0) {
                if (flash.warning !== lastWarning) {
                    lastWarning = flash.warning;
                    warningAlert(flash.warning);
                }
            } else {
                lastWarning = null;
            }
        },
        { immediate: true, deep: true },
    );
}
