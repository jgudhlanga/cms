import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { Address } from '@/types/shared';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { ZodObject } from 'zod';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';

export const useAddresses = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore, checkStatusIcon } = useDataTables();
	const createAddressColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans('trans.street_number'), accessorKey: 'attributes.address1' },
			{ header: trans('trans.street_name'), accessorKey: 'attributes.address2' },
			{ header: trans('trans.city_town_suburb'), accessorKey: 'attributes.address3' },
			{ header: trans_choice('trans.code', 1), accessorKey: 'attributes.address4' },
			{ header: trans('trans.address_5'), accessorKey: 'attributes.address5' },
			{ header: trans('trans.address_6'), accessorKey: 'attributes.address6' },
			{
				header: trans('trans.main'),
				accessorKey: 'isMain',
				meta: { align: 'center' },
				cell: ({ row }: {
					row: { original: Address }
				}) => checkStatusIcon(row.original?.attributes?.addressIsMain)
			},
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Address } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans('trans.bank_details');
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['create:addresses'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:addresses'], route('addresses.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:addresses'], route('addresses.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:addresses'], route('addresses.force-delete', id), name)
						}
					]);
				}
			}
		];
	};

	const onOpenModal = (can: boolean, address?: Address) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.addresses, edit: address });
	};

	const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
	const successMessage = () => trans('trans.item_saved', { item: trans_choice('trans.address', 1) });
	const errorMessage = () => trans('trans.item_save_failure', { item: trans_choice('trans.address', 1) });

	function validateForm(form: any) {
		mergeValidationSchema(schemaFields)(
			['addressTwoSchema', 'addressThreeSchema'], schemaFields['addressOneSchema']()
		).parse(form);
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
		updateAddress
	};
};
