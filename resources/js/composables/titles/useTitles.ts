import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Title } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useTitles = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const createTitleColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Title } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.name', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('titles.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('titles.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('titles.force-delete', id), name),
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
		{ transChoiceKey: 'title' },
	];

	const saveTitle = (form: InertiaForm<any>, title?: Title) => {
		const { nameSchema } = useSharedFormSchema();
		try {
			nameSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.title', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.title', 1) });
			if (title) {
				const id = getIdParams(title.id?.toString() ?? '');
				form.put(route('titles.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.titles));
			} else {
				form.post(route('titles.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.titles));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, title?: Title) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.titles, edit: title });
	};

	return {
		createTitleColumns,
		breadcrumbs,
		onOpenModal,
		saveTitle,
	};
};
