import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Relationship } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useRelationships = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const createRelationshipColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Relationship } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.name', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('relationships.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('relationships.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('relationships.force-delete', id), name),
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
		{ transChoiceKey: 'relationship' },
	];

	const saveRelationship = (form: InertiaForm<any>, relationship?: Relationship) => {
		const { nameSchema } = useSharedFormSchema();
		try {
			nameSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.relationship', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.relationship', 1) });
			if (relationship) {
				const id = getIdParams(relationship.id?.toString() ?? '');
				form.put(route('relationships.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.relationships));
			} else {
				form.post(route('relationships.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.relationships));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, relationship?: Relationship) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.relationships, edit: relationship });
	};

	return {
		createRelationshipColumns,
		breadcrumbs,
		onOpenModal,
		saveRelationship,
	};
};
