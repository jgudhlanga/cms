import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Race } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useRaces = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const createRaceColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Race } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.race', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('races.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('races.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('races.force-delete', id), name),
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
		{ transChoiceKey: 'race' },
	];

	const saveRace = (form: InertiaForm<any>, race?: Race) => {
		const { titleSchema } = useSharedFormSchema();
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.race', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.race', 1) });
			if (race) {
				const id = getIdParams(race.id?.toString() ?? '');
				form.put(route('races.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.races));
			} else {
				form.post(route('races.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.races));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, race?: Race) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.races, edit: race });
	};

	return {
		createRaceColumns,
		breadcrumbs,
		onOpenModal,
		saveRace,
	};
};
