import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Religion } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useReligions = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const religions = ref<Religion[]>([]);
    const { nameSchema } = useSharedFormSchema();
    const createReligionColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Religion } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.religion', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('religions.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('religions.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('religions.force-delete', id), name),
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
        { transChoiceKey: 'religion' },
    ];

    const saveReligion = (form: InertiaForm<any>, religion?: Religion) => {
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.religion', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.religion', 1) });
            if (religion) {
                const id = getIdParams(religion.id?.toString() ?? '');
                form.put(route('religions.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.religions));
            } else {
                form.post(route('religions.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.religions));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, religion?: Religion) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.religions, edit: religion });
    };

    const listReligions = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/religions?page_size=all', search, transChoiceKey: 'trans.religion' });
        isLoading.value = false;
        religions.value = data.value;
    };

    return {
        createReligionColumns,
        breadcrumbs,
        onOpenModal,
        saveReligion,
        isLoading,
        religions,
        listReligions,
    };
};
