import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { addressLabel } from '@/lib/addressFields';
import { forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { Address } from '@/types/shared';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useAddresses = () => {
    const { moreActionButton, onForceDelete, onRestore, checkStatusIcon } = useDataTables();
    const getName = () => trans_choice('trans.address', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const deletedMessage = () => trans('trans.item_deleted', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createAddressColumns = () => {
        return [
            { header: addressLabel(1), accessorKey: 'attributes.address1' },
            { header: addressLabel(2), accessorKey: 'attributes.address2' },
            { header: addressLabel(3), accessorKey: 'attributes.address3' },
            { header: addressLabel(4), accessorKey: 'attributes.address4' },
            { header: trans('trans.address_5'), accessorKey: 'attributes.address5' },
            { header: trans('trans.address_6'), accessorKey: 'attributes.address6' },
            {
                header: trans('trans.main'),
                accessorKey: 'isMain',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Address } }) => checkStatusIcon(row.original?.attributes?.addressIsMain),
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Address } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const studentAbility = 'manageOwnStudentContactDetails:students';
                    const restoreAbility = hasAbility(['restore:addresses', studentAbility]);
                    const purgeAbility = hasAbility(['forceDelete:addresses', studentAbility]);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => deleteAddress(String(id)),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(restoreAbility, route('addresses.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(purgeAbility, route('addresses.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const onOpenModal = (address?: Address) => {
        const allowed = hasAbility(['create:addresses', 'update:addresses', 'manageOwnStudentContactDetails:students']);
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.addresses, edit: address });
    };

    const deleteAddress = async (id: string) => {
        const deleteAbility = hasAbility(['delete:addresses', 'manageOwnStudentContactDetails:students']);
        if (!deleteAbility) return forbiddenAlert();
        const confirmed = await useCustomConfirmDialog().open({
            title: 'Delete Address',
            message: 'Are you sure you want to delete this address?',
            confirmText: 'Delete',
        });
        if (confirmed) {
            router.delete(route('addresses.destroy', id), {
                preserveScroll: true,
                onSuccess: () => {
                    successAlert(deletedMessage());
                    router.visit(window.location.href, { replace: true, preserveScroll: true });
                },
            });
        }
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    function validateForm(form: any) {
        mergeValidationSchema(schemaFields)(['addressTwoSchema', 'addressThreeSchema'], schemaFields['addressOneSchema']()).parse(form);
    }

    const updateAddress = (form: InertiaForm<any>, address?: Address) => {
        try {
            validateForm(form);
            const id = getIdParams(address?.id?.toString() ?? '');
            form.put(route('addresses.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.addresses));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createAddress = (form: InertiaForm<any>, postUrl: string) => {
        try {
            validateForm(form);
            form.post(postUrl, buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.addresses));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createAddressColumns,
        onOpenModal,
        createAddress,
        updateAddress,
        deleteAddress,
    };
};
