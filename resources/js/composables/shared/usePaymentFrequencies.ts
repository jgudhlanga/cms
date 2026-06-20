import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { PaymentFrequency } from '@/types/payments';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const usePaymentFrequencies = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const { titleSchema } = useSharedFormSchema();
    const createPaymentFrequencyColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: PaymentFrequency } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.payment_frequency', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('payment-frequencies.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('payment-frequencies.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('payment-frequencies.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        { transChoiceKey: 'settings', href: route('settings.index') },
        { transChoiceKey: 'payments_index', href: route('payments-index') },
        { transChoiceKey: 'payment_frequency' },
    ];

    const savePaymentFrequency = (form: InertiaForm<any>, paymentFrequency?: PaymentFrequency) => {
        try {
            titleSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.payment_frequency', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.payment_frequency', 1) });
            if (paymentFrequency) {
                const id = getIdParams(paymentFrequency.id?.toString() ?? '');
                form.put(route('payment-frequencies.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.payment_frequencies));
            } else {
                form.post(route('payment-frequencies.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.payment_frequencies));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, paymentFrequency?: PaymentFrequency) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.payment_frequencies, edit: paymentFrequency });
    };

    return {
        createPaymentFrequencyColumns,
        breadcrumbs,
        onOpenModal,
        savePaymentFrequency,
    };
};
