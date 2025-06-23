import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { MaritalStatus } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useMaritalStatuses = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const maritalStatuses = ref<MaritalStatus[]>([]);
    const createTableColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.title', 1), accessorKey: 'attributes.title' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: MaritalStatus } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.marital_status', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('marital-statuses.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('marital-statuses.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('marital-statuses.force-delete', id), name),
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
        { transChoiceKey: 'marital_status' },
    ];

    const saveMaritalStatus = (form: InertiaForm<any>, maritalStatus?: MaritalStatus) => {
        const { titleSchema } = useSharedFormSchema();
        try {
            titleSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.marital_status', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.marital_status', 1) });
            if (maritalStatus) {
                const id = getIdParams(maritalStatus?.id?.toString() ?? '');
                form.put(route('marital-statuses.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.marital_statuses));
            } else {
                form.post(route('marital-statuses.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.marital_statuses));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, maritalStatus?: MaritalStatus) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.marital_statuses, edit: maritalStatus });
    };

    const listMaritalStatuses = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/marital-statuses', search, transChoiceKey: 'trans.marital_status' });
        isLoading.value = false;
        maritalStatuses.value = data.value;
    };
    return {
        createTableColumns,
        breadcrumbs,
        onOpenModal,
        saveMaritalStatus,
        listMaritalStatuses,
        isLoading,
        maritalStatuses,
    };
};
