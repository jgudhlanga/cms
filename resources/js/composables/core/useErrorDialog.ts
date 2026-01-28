import { ref } from 'vue';

interface ConfirmOptions {
    title?: string;
    message?: string;
    note?: string;
    showCancelBtn?: boolean;
    confirmText?: string;
    cancelText?: string;
}

const isVisible = ref(false);
const options = ref<ConfirmOptions>({});
let resolveFn: ((confirmed: boolean) => void) | null = null;

export function useErrorDialog() {
    function open(newOptions: ConfirmOptions = {}): Promise<boolean> {
        options.value = {
            title: 'Error',
            message: 'An error has happened?',
            confirmText: 'Continue',
            cancelText: 'Cancel',
            showCancelBtn: false,
            note: 'Please rectify the error, to proceed',
            ...newOptions,
        };
        isVisible.value = true;

        return new Promise((resolve) => {
            resolveFn = resolve;
        });
    }

    function confirm() {
        isVisible.value = false;
        resolveFn?.(true);
        resolveFn = null;
    }

    function close() {
        isVisible.value = false;
        resolveFn?.(false);
        resolveFn = null;
    }

    return {
        isVisible,
        options,
        open,
        confirm,
        close,
    };
}
