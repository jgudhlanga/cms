import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { CommunicationMethod } from '@/types/communications';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useCommunicationMethods = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const { titleSchema } = useSharedFormSchema();

	const saveCommunicationMethod = (form: InertiaForm<any>, communicationMethod?: CommunicationMethod) => {
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.communication_mode', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.communication_mode', 1) });
			if (communicationMethod) {
				const id = getIdParams(communicationMethod.id?.toString() ?? '');
				form.put(route('communication-methods.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.communication_methods));
			} else {
				form.post(route('communication-methods.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.communication_methods));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const createMethodsColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: CommunicationMethod } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.communication_mode', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('communication-methods.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('communication-methods.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('communication-methods.force-delete', id), name),
						},
					]);
				},
			},
		];
	};

	const breadcrumbs: Array<Link> = [{ transChoiceKey: 'settings', href: route('settings.index') }, { transChoiceKey: 'communication_mode' }];

	const onOpenModal = (can: boolean, method?: CommunicationMethod) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.communication_methods, edit: method });
	};

	return {
		createMethodsColumns,
		breadcrumbs,
		onOpenModal,
		saveCommunicationMethod,
	};
};
