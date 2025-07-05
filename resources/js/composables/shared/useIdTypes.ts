import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { IdType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useIdTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const idTypes = ref<IdType[]>([]);
    const getName = () => trans_choice('trans.name', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const createIdTypeColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: IdType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('id-types.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('id-types.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('id-types.force-delete', id), getName()),
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
        { transChoiceKey: 'id_type' },
    ];

    const saveIdType = (form: InertiaForm<any>, idType?: IdType) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (idType) {
                const id = getIdParams(idType.id?.toString() ?? '');
                form.put(route('id-types.update', id), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.id_types));
            } else {
                form.post(route('id-types.store'), buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.id_types));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, idType?: IdType) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.id_types, edit: idType });
    };

    const listIdTypes = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: route('v1.id-types.index'), search, transChoiceKey: 'trans.id_type' });
        isLoading.value = false;
        idTypes.value = data.value;
    };

    return {
        createIdTypeColumns,
        breadcrumbs,
        onOpenModal,
        saveIdType,
        isLoading,
        idTypes,
        listIdTypes,
    };
};
