import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { FinanceExchangeRate } from '@/types/finance';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useFinanceExchangeRates = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();

    const createExchangeRateColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;

        return [
            { header: trans_choice('finance.date', 1), accessorKey: 'attributes.date' },
            { header: trans('finance.from'), accessorKey: 'attributes.currencyFrom' },
            { header: trans('finance.to'), accessorKey: 'attributes.currencyTo' },
            { header: trans_choice('finance.rate', 1), accessorKey: 'attributes.rate', meta: { align: 'right' } },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: FinanceExchangeRate } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('finance.exchange_rate', 1);

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:finance-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:finance-settings'], route('finance.exchange-rates.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:finance-settings'], route('finance.exchange-rates.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:finance-settings'], route('finance.exchange-rates.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        { transChoiceKey: 'finance.finance', transChoiceKeyIndex: 1, href: route('finance.index') },
        { transChoiceKey: 'finance.setting', href: route('finance.settings') },
        { transChoiceKey: 'finance.exchange_rate' },
    ];

    const saveExchangeRate = (form: InertiaForm<any>, exchangeRate?: FinanceExchangeRate) => {
        const success = trans('trans.item_saved', { item: trans_choice('finance.exchange_rate', 1) });
        const error = trans('trans.item_save_failure', { item: trans_choice('finance.exchange_rate', 1) });

        if (exchangeRate) {
            const id = getIdParams(exchangeRate.id?.toString() ?? '');
            form.put(route('finance.exchange-rates.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.finance_exchange_rates));
        } else {
            form.post(route('finance.exchange-rates.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.finance_exchange_rates));
        }
    };

    const onOpenModal = (can: boolean, exchangeRate?: FinanceExchangeRate) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.finance_exchange_rates, edit: exchangeRate });
    };

    return {
        createExchangeRateColumns,
        breadcrumbs,
        onOpenModal,
        saveExchangeRate,
    };
};

