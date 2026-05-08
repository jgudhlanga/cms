import { DEFAULT_AVATAR, DEFAULT_IMAGE, LOGO, PAYMENT_METHODS } from '@/lib/constants';
import { ref } from 'vue';

const defaultAvatarImage = ref(DEFAULT_AVATAR);
const defaultObjectImage = ref(DEFAULT_IMAGE);
const appLogo = ref(LOGO);
const paymentMethods = ref(PAYMENT_METHODS);

export function useDefaults() {
    return {
        defaultAvatarImage,
        defaultObjectImage,
        appLogo,
        paymentMethods,
    };
}
