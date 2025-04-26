import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Province } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { ref } from 'vue';
import { useDropdowns } from '@/composables/core/useDropdowns';

export const useProvinces = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const isLoading = ref(false);
	const provinces = ref<Province[]>([]);
	const createProvinceColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Province } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.province', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('provinces.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('provinces.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('provinces.force-delete', id), name)
						}
					]);
				}
			}
		];
	};

	const breadcrumbs: Array<Link> = [
		{
			transChoiceKey: 'settings',
			href: route('settings.index')
		},
		{ transChoiceKey: 'province' }
	];

	const saveProvince = (form: InertiaForm<any>, province?: Province) => {
		const { titleSchema } = useSharedFormSchema();
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.province', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.province', 1) });
			if (province) {
				const id = getIdParams(province.id?.toString() ?? '');
				form.put(route('provinces.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.provinces));
			} else {
				form.post(route('provinces.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.provinces));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, province?: Province) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.provinces, edit: province });
	};

	const listProvinces = async (search?: string) => {
		const { data, fetchData } = useDropdowns();
		isLoading.value = true;
		await fetchData({ url: '/v1/provinces', search, transChoiceKey: 'trans.province' });
		isLoading.value = false;
		provinces.value = data.value;
	};
	return {
		breadcrumbs,
		createProvinceColumns,
		isLoading,
		listProvinces,
		onOpenModal,
		provinces,
		saveProvince
	};
};
