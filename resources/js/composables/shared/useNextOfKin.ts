import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { NextOfKin } from '@/types/next-of-kin';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useNextOfKin = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const getName = () => trans('trans.next_of_kin');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createNextOfKinColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.relationship', 1), accessorKey: 'attributes.relationship' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: trans('trans.address_1'), accessorKey: 'attributes.address1' },
            { header: trans('trans.address_2'), accessorKey: 'attributes.address2' },
            { header: trans('trans.address_3'), accessorKey: 'attributes.address3' },
            { header: trans('trans.address_4'), accessorKey: 'attributes.address4' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: NextOfKin } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const deleteAbility = hasAbility('delete:next-of-kins');
                    const restoreAbility = hasAbility('restore:next-of-kins');
                    const purgeAbility = hasAbility('forceDelete:next-of-kins');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(deleteAbility, route('next-of-kins.destroy', id), getName()),
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
        const allowed = hasAbility(['create:next-of-kins', 'update:next-of-kins']);
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.next_of_kin, edit: nextOfKin });
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
    };
};
