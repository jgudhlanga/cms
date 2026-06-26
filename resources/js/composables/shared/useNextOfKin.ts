import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { addressLabel } from '@/lib/addressFields';
import { forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { NextOfKin } from '@/types/next-of-kin';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useNextOfKin = () => {
    const { moreActionButton, onForceDelete, onRestore } = useDataTables();
    const getName = () => trans('trans.next_of_kin');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const deletedMessage = () => trans('trans.item_deleted', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createNextOfKinColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.relationship', 1), accessorKey: 'attributes.relationship' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: addressLabel(1), accessorKey: 'attributes.address1' },
            { header: addressLabel(2), accessorKey: 'attributes.address2' },
            { header: addressLabel(3), accessorKey: 'attributes.address3' },
            { header: addressLabel(4), accessorKey: 'attributes.address4' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: NextOfKin } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    const restoreAbility = hasAbility('restore:next-of-kins');
                    const purgeAbility = hasAbility('forceDelete:next-of-kins');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => deleteNextOfKin(String(String(id))),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(restoreAbility, route('next-of-kins.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(purgeAbility, route('next-of-kins.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const onOpenModal = (nextOfKin?: NextOfKin) => {
        const allowed = hasAbility([
            'create:next-of-kins',
            'update:next-of-kins',
            'manageOwnStudentContactDetails:students',
        ]);
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.next_of_kin, edit: nextOfKin });
    };

    const deleteNextOfKin = async (id: string) => {
        const deleteAbility = hasAbility(['delete:next-of-kins', 'manageOwnStudentContactDetails:students']);
        if (!deleteAbility) return forbiddenAlert();
        const confirmed = await useCustomConfirmDialog().open({
            title: 'Delete Next of kin',
            message: 'Are you sure you want to delete this record?',
            confirmText: 'Delete',
        });
        if (confirmed) {
            router.delete(route('next-of-kins.destroy', id), {
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
        mergeValidationSchema(schemaFields)(
            ['nameSchema', 'phoneNumberSchema', 'addressTwoSchema', 'addressThreeSchema', 'relationshipSchema'],
            schemaFields['addressOneSchema'](),
        ).parse(form);
    }

    const updateNextOfKin = (form: InertiaForm<any>, nextOfKin?: NextOfKin) => {
        try {
            validateForm(form);
            const id = getIdParams(nextOfKin?.id?.toString() ?? '');
            form.put(route('next-of-kins.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.next_of_kin));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createNextOfKin = (form: InertiaForm<any>, postUrl: string) => {
        try {
            validateForm(form);
            form.post(postUrl, buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.next_of_kin));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createNextOfKinColumns,
        onOpenModal,
        createNextOfKin,
        updateNextOfKin,
        deleteNextOfKin,
    };
};
