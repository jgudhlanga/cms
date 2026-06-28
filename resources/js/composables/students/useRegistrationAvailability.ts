import { useUtils } from '@/composables/core/useUtils';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export type RegistrationAvailability = {
    isOpen: boolean;
    status?: 'suspended' | 'closed' | null;
    maintenanceUrl: string;
};

export const useRegistrationAvailability = () => {
    const { navigateTo } = useUtils();
    const page = usePage();

    const registration = computed(
        () =>
            (page.props.registration as RegistrationAvailability | undefined) ?? {
                isOpen: true,
                status: null,
                maintenanceUrl: route('portal.registration.maintenance'),
            },
    );

    const isRegistrationOpen = computed(() => registration.value.isOpen);

    const maintenanceUrl = computed(() => registration.value.maintenanceUrl);

    const redirectIfClosed = () => {
        if (!isRegistrationOpen.value) {
            navigateTo(maintenanceUrl.value);
        }
    };

    const redirectIfOpen = () => {
        if (isRegistrationOpen.value) {
            navigateTo(route('login'));
        }
    };

    const navigateToRegistrationOrMaintenance = (url: string) => {
        if (!isRegistrationOpen.value) {
            navigateTo(maintenanceUrl.value);
            return;
        }

        navigateTo(url);
    };

    return {
        registration,
        isRegistrationOpen,
        maintenanceUrl,
        redirectIfClosed,
        redirectIfOpen,
        navigateToRegistrationOrMaintenance,
    };
};
