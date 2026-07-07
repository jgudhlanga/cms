import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { AcademicYearOption } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useAcademicYearOptions = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const academicYearOptions = ref<AcademicYearOption[]>([]);
    const getName = () => trans_choice('academic_years.calendar_year_option', 1);
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
                cell: ({ row }: { row: { original: AcademicYearOption } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');

                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('academic-year-options.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('academic-year-options.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () =>
                                onForceDelete(hasAbility('forceDelete:settings'), route('academic-year-options.force-delete', id), getName()),
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
        { transChoiceKey: 'academic_years.calendar_year_option' },
    ];

    const save = (form: InertiaForm<any>, academicYearOption?: AcademicYearOption) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            if (academicYearOption) {
                const id = getIdParams(academicYearOption.id?.toString() ?? '');
                form.put(
                    route('academic-year-options.update', id),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.academic_year_options),
                );
            } else {
                form.post(
                    route('academic-year-options.store'),
                    buildFormOptions(form, successMessage(), errorMessage(), APP_MODULE_KEYS.academic_year_options),
                );
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const onOpenModal = (can: boolean, academicYearOption?: AcademicYearOption) => {
        if (!can) {
            return forbiddenAlert();
        }

        openModal({ name: APP_MODULE_KEYS.academic_year_options, edit: academicYearOption });
    };

    const list = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({
            url: 'api/v1/academic-year-options?page_size=all',
            search,
            transChoiceKey: 'academic_years.calendar_year_option',
        });
        isLoading.value = false;
        academicYearOptions.value = data.value;
    };

    return {
        createColumns,
        breadcrumbs,
        onOpenModal,
        save,
        isLoading,
        academicYearOptions,
        list,
    };
};
