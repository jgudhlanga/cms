import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { Contact } from '@/types/shared';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ZodObject } from 'zod';

export const useContacts = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, checkStatusIcon } = useDataTables();

    const getName = () => trans_choice('trans.contact', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createContactColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: trans('trans.alt_phone_number'), accessorKey: 'attributes.altPhoneNumber' },
            { header: trans('trans.email_address'), accessorKey: 'attributes.emailAddress' },
            { header: trans('trans.alt_email_address'), accessorKey: 'attributes.altEmailAddress' },
            {
                header: trans('trans.main'),
                accessorKey: 'isMain',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Contact } }) => checkStatusIcon(row.original?.attributes?.contactIsMain),
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Contact } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const studentAbility = 'manageOwnStudentContactDetails:students';
                    const deleteAbility = hasAbility(['delete:contacts', studentAbility]);
                    const restoreAbility = hasAbility(['restore:contacts', studentAbility]);
                    const purgeAbility = hasAbility(['forceDelete:contacts', studentAbility]);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(deleteAbility, route('contacts.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(restoreAbility, route('contacts.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(purgeAbility, route('contacts.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const onOpenModal = (contact?: Contact) => {
        const allowed = hasAbility(['create:contacts', 'update:contacts', 'manageOwnStudentContactDetails:students']);
        if (!allowed) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.contacts, edit: contact });
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;

    function validateForm(form: any) {
        mergeValidationSchema(schemaFields)(['phoneNumberSchema', 'emailAddressSchema'], schemaFields['nameSchema']()).parse(form);
    }

    const updateContact = (form: InertiaForm<any>, contact?: Contact) => {
        try {
            validateForm(form);
            const id = getIdParams(contact?.id?.toString() ?? '');
            form.put(route('contacts.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.contacts));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createContact = (form: InertiaForm<any>, postUrl: string) => {
        try {
            validateForm(form);
            form.post(postUrl, buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.contacts));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createContactColumns,
        onOpenModal,
        updateContact,
        createContact,
    };
};
