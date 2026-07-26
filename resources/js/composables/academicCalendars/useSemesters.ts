import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { Semester } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useSemesters = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const semesters = ref<Semester[]>([]);
    const getName = () => trans_choice('academic_years.semester', 1);
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });

    const createColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Semester } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:semesters'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:semesters'), route('semesters.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:semesters'), route('semesters.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () =>
                                onForceDelete(hasAbility('forceDelete:semesters'), route('semesters.force-delete', id), getName()),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'institution',
            href: route('institution.index'),
        },
        {
            transKey: 'institution_setup',
            href: route('institution.setup'),
        },
        { transChoiceKey: 'academic_years.semester' },
    ];

    const save = (form: InertiaForm<any>, semester?: Semester) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (semester) {
                const id = getIdParams(semester.id?.toString() ?? '');
                form.put(
                    route('semesters.update', id),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.semesters),
                );
            } else {
                form.post(
                    route('semesters.store'),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.semesters),
                );
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const onOpenModal = (can: boolean, semester?: Semester) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.semesters, edit: semester });
    };

    const list = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/semesters?page_size=all',
            search,
            transChoiceKey: 'academic_years.semester',
        });
        isLoading.value = false;
        semesters.value = data.value;
    };

    return {
        createColumns,
        breadcrumbs,
        onOpenModal,
        save,
        isLoading,
        semesters,
        list,
    };
};
