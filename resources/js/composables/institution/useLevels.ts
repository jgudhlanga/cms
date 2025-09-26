import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Level } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useLevels = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const levels = ref<Level[]>([]);
    const createLevelColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('#', 1), accessorKey: 'attributes.position', meta: { align: 'left' } },
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            { header: trans('trans.allowed_applications_per_level'), accessorKey: 'attributes.allowedApplicationsPerLevel', meta: { align: 'center' } },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Level } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.level', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('levels.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('levels.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('levels.force-delete', id), name),
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
        { transChoiceKey: 'level' },
    ];

    const saveLevel = (form: InertiaForm<any>, level?: Level) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.level', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 1) });
            if (level) {
                const id = getIdParams(level.id?.toString() ?? '');
                form.put(route('levels.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.levels));
            } else {
                form.post(route('levels.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.levels));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, level?: Level) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.levels, edit: level });
    };

    const listLevels = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/levels?page_size=all', search, transChoiceKey: 'trans.level' });
        isLoading.value = false;
        levels.value = data.value;
    };

    return {
        createLevelColumns,
        breadcrumbs,
        onOpenModal,
        saveLevel,
        levels,
        listLevels,
        isLoading,
    };
};
