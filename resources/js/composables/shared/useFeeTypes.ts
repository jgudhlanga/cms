import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { FeeType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useFeeTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const feeTypes = ref<FeeType[]>([]);
    const getName = () => trans_choice('trans.fee_type', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createFeeTypeColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: FeeType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('fee-types.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('fee-types.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('fee-types.force-delete', id), getName()),
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
        { transChoiceKey: 'fee_type' },
    ];

    const saveFeeType = (form: InertiaForm<any>, feeType?: FeeType) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (feeType) {
                const id = getIdParams(feeType.id?.toString() ?? '');
                form.put(route('fee-types.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.fee_types));
            } else {
                form.post(route('fee-types.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.fee_types));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, feeType?: FeeType) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.fee_types, edit: feeType });
    };

    const listFeeTypes = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/fee-types?page_size=all',
            search,
            transChoiceKey: 'trans.fee_type',
        });
        isLoading.value = false;
        feeTypes.value = data.value;
    };

    return {
        createFeeTypeColumns,
        breadcrumbs,
        onOpenModal,
        saveFeeType,
        isLoading,
        feeTypes,
        listFeeTypes,
    };
};
