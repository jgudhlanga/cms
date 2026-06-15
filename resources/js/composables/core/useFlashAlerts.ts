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

    watch(
        () => page.props.flash as FlashProps | undefined,
        (flash) => {
            if (!flash) {
                return;
            }

            if (typeof flash.success === 'string' && flash.success.length > 0) {
                successAlert(flash.success);
            }

            if (typeof flash.error === 'string' && flash.error.length > 0) {
                errorAlert(flash.error);
            }

            if (typeof flash.warning === 'string' && flash.warning.length > 0) {
                warningAlert(flash.warning);
            }
        },
        { immediate: true, deep: true },
    );
}
