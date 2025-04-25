import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Language } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useLanguages = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const { titleSchema } = useSharedFormSchema();
	const createLanguageColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: Language } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.language', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('languages.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('languages.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('languages.force-delete', id), name),
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
		{ transChoiceKey: 'language' },
	];

	const saveLanguage = (form: InertiaForm<any>, language?: Language) => {
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.language', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.language', 1) });
			if (language) {
				const id = getIdParams(language.id?.toString() ?? '');
				form.put(route('languages.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.languages));
			} else {
				form.post(route('languages.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.languages));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, language?: Language) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.languages, edit: language });
	};

	return {
		createLanguageColumns,
		breadcrumbs,
		onOpenModal,
		saveLanguage,
	};
};
