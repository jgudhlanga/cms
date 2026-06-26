import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { addressLabel } from '@/lib/addressFields';
import { forbiddenAlert, openModal, successAlert } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { Sponsor } from '@/types/students';
import { InertiaForm, router } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useSponsors = () => {
    const { moreActionButton, onForceDelete, onRestore } = useDataTables();
    const getName = () => trans_choice('trans.sponsor', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const deletedMessage = () => trans('trans.item_deleted', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const studentAbility = 'manageOwnStudentSponsorDetails:students';
    const adminAbility = 'manageStudentMetadata:admin';
    const allowed = hasAbility([adminAbility, studentAbility]);
    const createSponsorColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.sponsor_type', 1), accessorKey: 'attributes.sponsorType' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: trans('trans.email_address'), accessorKey: 'attributes.email' },
            { header: addressLabel(1), accessorKey: 'attributes.address1' },
            { header: addressLabel(2), accessorKey: 'attributes.address2' },
            { header: addressLabel(3), accessorKey: 'attributes.address3' },
            { header: addressLabel(4), accessorKey: 'attributes.address4' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Sponsor } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => deleteSponsor(String(id)),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(allowed, route('sponsors.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(allowed, route('sponsors.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const onOpenModal = (sponsor?: Sponsor) => {
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.sponsors, edit: sponsor });
    };

    const deleteSponsor = async (id: string) => {
        const deleteAbility = hasAbility(['delete:addresses', 'manageOwnStudentContactDetails:students']);
        if (!deleteAbility) return forbiddenAlert();
        const confirmed = await useCustomConfirmDialog().open({
            title: 'Delete Sponsor',
            message: 'Are you sure you want to delete this speonsor?',
            confirmText: 'Delete',
        });
        if (confirmed) {
            router.delete(route('sponsors.destroy', id), {
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
        mergeValidationSchema(schemaFields)(['phoneNumberSchema'], schemaFields['nameSchema']()).parse(form);
    }

    const updateSponsor = (form: InertiaForm<any>, sponsor?: Sponsor) => {
        try {
            validateForm(form);
            const id = getIdParams(sponsor?.id?.toString() ?? '');
            form.put(route('sponsors.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.sponsors));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createSponsor = (form: InertiaForm<any>) => {
        try {
            validateForm(form);
            form.post(route('sponsors.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.sponsors));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createSponsorColumns,
        onOpenModal,
        updateSponsor,
        createSponsor,
        allowed,
        deleteSponsor,
    };
};
