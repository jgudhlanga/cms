import { toast, ToastPosition, ToastTheme } from 'vue3-toastify';

interface ToastOption {
    position: ToastPosition;
    autoClose: number | boolean;
    theme?: ToastTheme;
}

export interface ToastInterface {
    error: (message: string) => void;
    info: (message: string) => void;
    success: (message: string) => void;
    warning: (message: string) => void;
}

const toastOptions: ToastOption = {
    position: toast.POSITION.BOTTOM_RIGHT,
    autoClose: 6000,
    theme: 'colored',
};

class ToastService implements ToastInterface {
    error(message: string): void {
        toast.error(message, toastOptions);
    }

    info(message: string): void {
        toast.info(message, toastOptions);
    }

    success(message: string): void {
        toast.success(message, toastOptions);
    }

    warning(message: string): void {
        toast.warn(message, toastOptions);
    }
}

export default new ToastService();
