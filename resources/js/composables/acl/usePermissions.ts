import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { type Permission } from '@/types/acl';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const usePermissions = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();

	const { nameSchema } = useSharedFormSchema();

	const savePermission = (form: InertiaForm<any>, permission?: Permission) => {
		try {
			nameSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.permission', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.permission', 1) });
			if (permission) {
				const id = getIdParams(permission.id?.toString() ?? '');
				form.put(route('permissions.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.permissions));
			} else {
				form.post(route('permissions.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.permissions));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const createPermissionColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
			{ header: trans('trans.guard_name'), accessorKey: 'attributes.guardName' },
			{ header: trans('trans.description'), accessorKey: 'attributes.description' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Permission } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.permission', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:permissions'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:permissions'], route('permissions.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:permissions'], route('permissions.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:permissions'], route('permissions.force-delete', id), name),
						},
					]);
				},
			},
		];
	};

	const breadcrumbs: Array<Link> = [
		{ transChoiceKey: 'settings', href: route('settings.index') },
		{ transChoiceKey: 'acl', href: route('acl.index') },
		{ transChoiceKey: 'permission' },
	];

	const onOpenModal = (can: boolean, edit?: Permission) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.permissions, edit: edit });
	};

	return {
		createPermissionColumns,
		breadcrumbs,
		onOpenModal,
		savePermission,
	};
};
