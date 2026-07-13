import AccountPurgeArchiveFlushConfirmDialog from '@/components/account-purge/AccountPurgeArchiveFlushConfirmDialog.vue';
import AccountPurgeArchiveRestoreConfirmDialog from '@/components/account-purge/AccountPurgeArchiveRestoreConfirmDialog.vue';
import type { AccountPurgeArchiveDialogTarget } from '@/types/maintenance-archives';
import { useModal } from 'vue-final-modal';

export const toArchiveDialogTarget = (archive: {
    id: number;
    attributes: {
        name: string | null;
        email: string | null;
        purgeType: AccountPurgeArchiveDialogTarget['purgeType'];
        purgeTypeLabel: string;
    };
}): AccountPurgeArchiveDialogTarget => ({
    id: archive.id,
    name: archive.attributes.name ?? archive.attributes.email ?? '—',
    email: archive.attributes.email,
    purgeType: archive.attributes.purgeType,
    purgeTypeLabel: archive.attributes.purgeTypeLabel,
});

export const openArchiveRestoreDialog = (
    archive: AccountPurgeArchiveDialogTarget,
    onConfirm: () => void,
): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: AccountPurgeArchiveRestoreConfirmDialog,
        attrs: {
            archive,
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

export const openArchiveFlushDialog = (
    archive: AccountPurgeArchiveDialogTarget,
    onConfirm: () => void,
): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: AccountPurgeArchiveFlushConfirmDialog,
        attrs: {
            archive,
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
