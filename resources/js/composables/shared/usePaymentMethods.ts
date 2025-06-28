import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { PaymentMethod } from '@/types/payments';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const usePaymentMethods = () => {
	const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
	const createPaymentMethodColumns = () => {
		const { props } = usePage();
		const { can } = props?.auth as Auth;
		return [
			{ header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
			{
				header: trans_choice('trans.action', 2),
				accessorKey: 'actions',
				enableSorting: false,
				meta: { align: 'right' },
				cell: ({ row }: { row: { original: PaymentMethod } }) => {
					const id = getIdParams(row.original.id?.toString() ?? '');
					const name = trans_choice('trans.payment_method', 1);
					return moreActionButton(!!row.original?.attributes?.deletedAt, [
						{ key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
						{
							key: 'archive',
							action: () => onDelete(can['delete:settings'], route('payment-methods.destroy', id), name),
						},
						{
							key: 'restore',
							action: () => onRestore(can['restore:settings'], route('payment-methods.restore', id), name),
						},
						{
							key: 'delete',
							action: () => onForceDelete(can['forceDelete:settings'], route('payment-methods.force-delete', id), name),
						},
					]);
				},
			},
		];
	};

	const breadcrumbs: Array<Link> = [
		{ transChoiceKey: 'settings', href: route('settings.index') },
		{ transChoiceKey: 'payments_index', href: route('payments-index') },
		{ transChoiceKey: 'payment_method' },
	];

	const savePaymentMethod = (form: InertiaForm<any>, paymentMethod?: PaymentMethod) => {
		const { nameSchema } = useSharedFormSchema();
		try {
			nameSchema().parse(form);
			const success = trans('trans.item_saved', { item: trans_choice('trans.payment_method', 1) });
			const error = trans('trans.item_save_failure', { item: trans_choice('trans.payment_method', 1) });
			if (paymentMethod) {
				const id = getIdParams(paymentMethod.id?.toString() ?? '');
				form.put(route('payment-methods.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.payment_methods));
			} else {
				form.post(route('payment-methods.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.payment_methods));
			}
		} catch (error: any) {
			form.setError(error.format());
		}
	};
	const onOpenModal = (can: boolean, paymentMethod?: PaymentMethod) => {
		if (!can) return forbiddenAlert();
		openModal({ name: APP_MODULE_KEYS.payment_methods, edit: paymentMethod });
	};

	return {
		createPaymentMethodColumns,
		breadcrumbs,
		onOpenModal,
		savePaymentMethod,
	};
};
