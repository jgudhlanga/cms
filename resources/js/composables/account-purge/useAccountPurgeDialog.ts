import AccountPurgeConfirmDialog, {
    type AccountPurgeDialogUser,
} from '@/components/account-purge/AccountPurgeConfirmDialog.vue';
import { useModal } from 'vue-final-modal';

export type { AccountPurgeDialogUser };

export interface OpenAccountPurgeDialogOptions {
    users: AccountPurgeDialogUser[];
    purgeItems: string[];
    introTranslationKey?: string;
    onConfirm: (reason: string) => void;
}

export const openAccountPurgeDialog = ({
    users,
    purgeItems,
    introTranslationKey,
    onConfirm,
}: OpenAccountPurgeDialogOptions): void => {
    const { open, destroy } = useModal({
        defaultModelValue: false,
        keepAlive: false,
        component: AccountPurgeConfirmDialog,
        attrs: {
            users,
            purgeItems,
            introTranslationKey,
            onConfirm: (reason: string) => {
                onConfirm(reason);
                destroy();
            },
            onClosed: () => {
                destroy();
            },
        },
    });

    void open();
};

export const maintenanceUserPurgeItems = [
    'trans.maintenance_users_purge_item_account',
    'trans.maintenance_users_purge_item_roles',
    'trans.maintenance_users_purge_item_preferences',
    'trans.maintenance_users_purge_item_ledgers',
    'trans.maintenance_users_purge_item_tokens',
    'trans.maintenance_users_purge_item_avatar',
] as const;

export const studentAccountPurgeItems = [
    'trans.student_account_purge_item_profile',
    'trans.student_account_purge_item_applications',
    'trans.student_account_purge_item_enrolments',
    'trans.student_account_purge_item_financials',
    'trans.student_account_purge_item_accommodation',
    'trans.student_account_purge_item_documents',
    'trans.student_account_purge_item_contacts',
    'trans.student_account_purge_item_user',
] as const;

export const toPurgeDialogUser = (user: {
    attributes: { name: string; email: string };
}): AccountPurgeDialogUser => ({
    name: user.attributes.name,
    email: user.attributes.email,
});
