import {
    openAccountPurgeDialog,
    maintenanceUserPurgeItems,
    toPurgeDialogUser,
    type AccountPurgeDialogUser,
} from '@/composables/account-purge/useAccountPurgeDialog';

export const openMaintenancePurgeDialog = (
    users: AccountPurgeDialogUser[],
    onConfirm: (reason: string) => void,
): void => {
    openAccountPurgeDialog({
        users,
        purgeItems: [...maintenanceUserPurgeItems],
        onConfirm,
    });
};

export { toPurgeDialogUser };
