import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { AddressType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useAddressTypes = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const { titleSchema } = useSharedFormSchema();
	const isLoading = ref(false);
	const addressTypes = ref<AddressType[]>([]);
	const createAddressTypeColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{ header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: AddressType } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.address_type', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('address-types.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('address-types.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('address-types.force-delete', id), name),
						},
					]);
				},
			},
		];
	};

	const breadcrumbs: Array<Link> = [
		{
			transChoiceKey: 'settings',
			href: route('settings.index'),
		},
		{ transChoiceKey: 'address_type' },
	];
	const saveAddressType = (form: InertiaForm<any>, addressType?: AddressType) => {
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.address_type', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.address_type', 1) });
			if (addressType) {
				const id = getIdParams(addressType.id?.toString() ?? '');
				form.put(route('address-types.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.address_types));
			} else {
				form.post(route('address-types.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.address_types));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, addressType?: AddressType) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.address_types, edit: addressType });
	};

	const listAddressTypes = async (search?: string) => {
		const { data, fetchData } = useDropdowns();
		isLoading.value = true;
		await fetchData({ url: route('v1.banks.index'), search, transChoiceKey: 'trans.bank' });
		isLoading.value = false;
		addressTypes.value = data.value;
	};

	return {
		addressTypes,
		breadcrumbs,
		createAddressTypeColumns,
		isLoading,
		listAddressTypes,
		onOpenModal,
		saveAddressType,
	};
};
