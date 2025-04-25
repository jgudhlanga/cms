import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { TradingStatus } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { useDropdowns } from '@/composables/core/useDropdowns';

export const useTradingStatuses = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const isLoading = ref(false);
	const tradingStatuses = ref<TradingStatus[]>([]);
	const createTradingStatusColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: TradingStatus } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.trading_status', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('trading-statuses.destroy', id), name)
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('trading-statuses.restore', id), name)
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('trading-statuses.force-delete', id), name)
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
		{ transChoiceKey: 'trading_status' }
	];

	const saveTradingStatus = (form: InertiaForm<any>, status?: TradingStatus) => {
		const { titleSchema } = useSharedFormSchema();
		try {
			titleSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.trading_status', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.trading_status', 1) });
			if (status) {
				const id = getIdParams(status.id?.toString() ?? '');
				form.put(route('trading-statuses.create', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.trading_statuses));
			} else {
				form.post(route('trading-statuses.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.trading_statuses));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, status?: TradingStatus) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.trading_statuses, edit: status });
	};

	const listTradingStatuses = async (search?: string) => {
		const { data, fetchData } = useDropdowns();
		isLoading.value = true;
		await fetchData({ url: '/v1/trading-statuses', search, transChoiceKey: 'trans.trading_status' });
		isLoading.value = false;
		tradingStatuses.value = data.value;
	};

	return {
		createTradingStatusColumns,
		breadcrumbs,
		onOpenModal,
		saveTradingStatus,
		isLoading,
		tradingStatuses,
		listTradingStatuses
	};
};
