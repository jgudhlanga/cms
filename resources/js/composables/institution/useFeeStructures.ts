import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useUtils } from '@/composables/core/useUtils';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { FeeStructure } from '@/types/institution';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import {FeeType} from "@/types/settings";
import {hasAbility} from "@/lib/permissions";

export const useFeeStructures = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const { formatCurrency } = useUtils();
    const isLoading = ref(false);
    const feeStructures = ref<FeeStructure[]>([]);
    const createFeeStructureColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.level', 1), accessorKey: 'attributes.level' },
            { header: trans_choice('trans.fee_type', 1), accessorKey: 'attributes.feeType' },
            {
                header: trans('trans.amount_in_us'),
                accessorKey: 'attributes.localFcaAmount',
                cell: ({ row }: { row: { original: FeeStructure } }) => {
                    return formatCurrency(row.original?.attributes?.localFcaAmount);
                },
            },
            {
                header: trans('trans.local_amount'),
                accessorKey: 'attributes.amount',
                cell: ({ row }: { row: { original: FeeStructure } }) => {
                    return formatCurrency(row.original?.attributes?.amount);
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: FeeStructure } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.fee_structure', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(row.original, undefined) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:fee-structures'], route('fee-structures.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:fee-structures'], route('fee-structures.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:fee-structures'], route('fee-structures.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const saveFeeStructure = (form: InertiaForm<any>, feeStructure?: FeeStructure) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.fee_structure', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.fee_structure', 1) });
            if (feeStructure) {
                const id = getIdParams(feeStructure.id?.toString() ?? '');
                form.put(route('fee-structures.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.fee_structures));
            } else {
                form.post(route('fee-structures.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.fee_structures));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (feeStructure?: FeeStructure, feeType?: FeeType) => {
        if (!hasAbility(['create:fee-structures', 'update:fee-structures'])) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.fee_structures, edit: feeStructure, parent: feeType });
    };

    const listFeeStructures = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/fee-structures?page_size=100', search, transChoiceKey: 'trans.fee_structure' });
        isLoading.value = false;
        feeStructures.value = data.value;
    };

    return {
        createFeeStructureColumns,
        onOpenModal,
        saveFeeStructure,
        feeStructures,
        listFeeStructures,
        isLoading,
    };
};
