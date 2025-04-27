import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { Contact } from '@/types/shared';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { ZodObject } from 'zod';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';

export const useContacts = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore, checkStatusIcon } = useDataTables();
	const createContactColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
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
				cell: ({ row }: {
					row: { original: Contact }
				}) => checkStatusIcon(row.original?.attributes?.contactIsMain)
			},
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Contact } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans('trans.bank_details');
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:contacts'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:contacts'], route('contacts.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:contacts'], route('contacts.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:contacts'], route('contacts.force-delete', id), name)
						}
					]);
				}
			}
		];
	};

	const onOpenModal = (can: boolean, contact?: Contact) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.contacts, edit: contact });
	};

	const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
	const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.contact', 1) });
	const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.contact', 1) });

	function validateForm(form: any) {
		mergeValidationSchema(schemaFields)(
			['phoneNumberSchema', 'emailAddressSchema'], schemaFields['nameSchema']()
		).parse(form);
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
		createContact
	};
};
