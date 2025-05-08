import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { DepartmentLevel } from '@/types/institution';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useDepartmentLevels = () => {
    const { moreActionButton, textLink } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const createDepartmentLevelColumns = () => {
        return [
            {
                header: trans_choice('trans.level', 1),
                accessorKey: 'level',
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('institution-departments.show', id), row.original.attributes?.level);
                },
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: DepartmentLevel } }) => {
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'view', action: () => {} },
                        {
                            key: 'archive',
                            action: () => {},
                        },
                        {
                            key: 'restore',
                            action: () => {},
                        },
                        {
                            key: 'delete',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };


    const syncDepartmentLevels = (institutionDepartmentId: string, form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.level', 2) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.level', 2) });
            form.post(route('department-levels.sync', institutionDepartmentId), buildFormOptions(form, success, error, APP_MODULE_KEYS.department_levels));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openDepartmentLevelsModal = (departmentLevels: Array<string | undefined | null> | null) => {
        if (!can['create:department-metadata']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.department_levels, edit: departmentLevels });
    };


    return {
        createDepartmentLevelColumns,
        openDepartmentLevelsModal,
        syncDepartmentLevels,
    };
};
