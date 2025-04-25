import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Module } from '@/types/acl';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useModules = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const { titleSchema } = useSharedFormSchema();

	const saveModule = (form: InertiaForm<any>, module?: Module) => {
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.module', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.module', 1) });
			if (module) {
				const id = getIdParams(module.id?.toString() ?? '');
				form.put(route('modules.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.modules));
			} else {
				form.post(route('modules.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.modules));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const createModuleColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{ header: trans('trans.description'), accessorKey: 'attributes.description' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Module } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.module', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:modules'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:modules'], route('modules.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:modules'], route('modules.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:modules'], route('modules.force-delete', id), name),
						},
					]);
				},
			},
		];
	};

	const breadcrumbs: Array<Link> = [
		{ transChoiceKey: 'settings', href: route('settings.index') },
		{ transChoiceKey: 'acl', href: route('acl.index') },
		{ transChoiceKey: 'module' },
	];

	const onOpenModal = (can: boolean, edit?: Module) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.modules, edit: edit });
	};

	return {
		createModuleColumns,
		breadcrumbs,
		onOpenModal,
		saveModule,
	};
};
