import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { Grade } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useGrades = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const createGradeColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('#', 1), accessorKey: 'attributes.position', meta: { align: 'left' } },
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Grade } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.grade', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('grades.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('grades.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('grades.force-delete', id), name),
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
        { transChoiceKey: 'grade' },
    ];

    const saveGrade = (form: InertiaForm<any>, grade?: Grade) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.grade', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.grade', 1) });
            if (grade) {
                const id = getIdParams(grade.id?.toString() ?? '');
                form.put(route('grades.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.grades));
            } else {
                form.post(route('grades.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.grades));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, grade?: Grade) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.grades, edit: grade });
    };

    const isLoading = ref(false);
    const grades = ref<Grade[]>([]);

    const listGrades = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: 'api/v1/grades?page_size=100', search, transChoiceKey: 'trans.grade' });
        isLoading.value = false;
        grades.value = data.value;
    };

    return {
        createGradeColumns,
        breadcrumbs,
        onOpenModal,
        saveGrade,
        isLoading,
        grades,
        listGrades,
    };
};
