import { useDataTables } from '@/composables/core/useDataTables';
import { useDropdowns } from '@/composables/core/useDropdowns';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import { EmploymentType } from '@/types/settings';
import type { Link } from '@/types/ui';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useEmploymentTypes = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore } = useDataTables();
    const isLoading = ref(false);
    const employmentTypes = ref<EmploymentType[]>([]);
    const createEmploymentTypeColumns = () => {
        return [
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.description'), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: EmploymentType } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.name', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:settings'), row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:settings'), route('employment-types.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:settings'), route('employment-types.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:settings'), route('employment-types.force-delete', id), name),
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
        { transChoiceKey: 'employment_type' },
    ];

    const saveEmploymentType = (form: InertiaForm<any>, employmentType?: EmploymentType) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.employment_type', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.employment_type', 1) });
            if (employmentType) {
                const id = getIdParams(employmentType.id?.toString() ?? '');
                form.put(route('employment-types.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.employment_types));
            } else {
                form.post(route('employment-types.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.employment_types));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, employmentType?: EmploymentType) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.employment_types, edit: employmentType });
    };

    const listEmploymentTypes = async (search?: string) => {
        const { data, fetchData } = useDropdowns();
        isLoading.value = true;
        await fetchData({ url: route('v1.employment-types.index'), search, transChoiceKey: 'trans.employment_type' });
        isLoading.value = false;
        employmentTypes.value = data.value;
    };

    return {
        createEmploymentTypeColumns,
        breadcrumbs,
        onOpenModal,
        saveEmploymentType,
        isLoading,
        employmentTypes,
        listEmploymentTypes,
    };
};
