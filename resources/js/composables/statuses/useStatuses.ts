import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Status } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { ref } from 'vue';

export const useStatuses = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const isLoading = ref(false);
	const statuses = ref<Status[]>([]);
	const createStatusColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Status } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.status', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('statuses.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('statuses.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('statuses.force-delete', id), name)
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
		{ transChoiceKey: 'status' }
	];

	const saveStatus = (form: InertiaForm<any>, status?: Status) => {
		const { titleSchema } = useSharedFormSchema();
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.status', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.status', 1) });
			if (status) {
				const id = getIdParams(status.id?.toString() ?? '');
				form.put(route('statuses.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.statuses));
			} else {
				form.post(route('statuses.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.statuses));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, status?: Status) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.statuses, edit: status });
	};

	const listStatuses = async (search?: string) => {
		const { data, fetchData } = useDropdowns();
		isLoading.value = true;
		await fetchData({ url: '/v1/statuses', search, transChoiceKey: 'trans.status' });
		isLoading.value = false;
		statuses.value = data.value;
	};
	return {
		createStatusColumns,
		breadcrumbs,
		onOpenModal,
		saveStatus,
		listStatuses,
		isLoading,
		statuses
	};
};
