import MaintenanceUserPurgeConfirmDialog, {
    type MaintenancePurgeDialogUser,
} from '@/pages/maintenance/partials/MaintenanceUserPurgeConfirmDialog.vue';
import { useModal } from 'vue-final-modal';

export const openMaintenancePurgeDialog = (
    users: MaintenancePurgeDialogUser[],
    onConfirm: () => void,
): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: MaintenanceUserPurgeConfirmDialog,
        attrs: {
            users,
            onConfirm: () => {
                onConfirm();
                destroy();
            },
            onClosed: () => {
                destroy();
            },
        },
    });

    void open();
};

export const toPurgeDialogUser = (user: {
    attributes: { name: string; email: string };
}): MaintenancePurgeDialogUser => ({
    name: user.attributes.name,
    email: user.attributes.email,
});
