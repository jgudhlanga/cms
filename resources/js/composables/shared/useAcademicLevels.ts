import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { AcademicLevel } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useAcademicLevels = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, orderButtons } = useDataTables();
    const isLoading = ref(false);
    const academicLevels = ref<AcademicLevel[]>([]);
    const createAcademicLevelColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans_choice('trans.position', 1),
                accessorKey: 'attributes.position',
                meta: { align: 'center' },
            },
            {
                header: trans('trans.order'),
                accessorKey: 'order',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: AcademicLevel } }) => orderButtons(),
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: AcademicLevel } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.level', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:settings'], route('academic-levels.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:settings'], route('academic-levels.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:settings'], route('academic-levels.force-delete', id), name),
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
        { transChoiceKey: 'academic_level' },
    ];

    const saveAcademicLevel = (form: InertiaForm<any>, academicLevel?: AcademicLevel) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.academic_level', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.academic_level', 1) });
            if (academicLevel) {
                const id = getIdParams(academicLevel.id?.toString() ?? '');
                form.put(route('academic-levels.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.academic_levels));
            } else {
                form.post(route('academic-levels.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.academic_levels));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, academicLevel?: AcademicLevel) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.academic_levels, edit: academicLevel });
    };

    const listAcademicLevels = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/academic-levels?page_size=100',
            search,
            transChoiceKey: 'trans.academic_level',
        });
        isLoading.value = false;
        academicLevels.value = data.value;
    };

    return {
        createAcademicLevelColumns,
        breadcrumbs,
        onOpenModal,
        saveAcademicLevel,
        academicLevels,
        listAcademicLevels,
        isLoading,
    };
};
