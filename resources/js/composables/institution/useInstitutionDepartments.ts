import { useDataTables } from '@/composables/core/useDataTables';
import { forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import { Auth } from '@/types';
import { InstitutionDepartment } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';

export const useInstitutionDepartments = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, onView, textLink } = useDataTables();
    const { props } = usePage();
    const { can } = props?.auth as Auth;
    const createInstitutionDepartmentColumns = () => {
        return [
            {
                header: trans_choice('trans.department', 1),
                accessorKey: 'department',
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('institution-departments.show', id), row.original.attributes?.department);
                },
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    const id = row.original.id?.toString() ?? '';
                    const name = trans_choice('trans.department', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'view', action: () => viewDepartment(id) },
                        {
                            key: 'archive',
                            action: () => archiveDepartment(route('institution-departments.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => restoreDepartment(route('institution-departments.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => deleteDepartment(route('institution-departments.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [
        {
            transChoiceKey: 'institution',
            transChoiceKeyIndex: 1,
            href: route('institution.index'),
        },
        { transChoiceKey: 'department' },
    ];

    const syncInstitutionDepartments = (form: InertiaForm<any>) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.department', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.department', 1) });
            form.post(route('institution-departments.sync'), buildFormOptions(form, success, error, APP_MODULE_KEYS.institution_departments));
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const openInstitutionDepartmentsModal = (institutionDepartments: Array<string | undefined | null> | null) => {
        if (!can['create:institution-departments']) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.institution_departments, edit: institutionDepartments });
    };

    const viewDepartment = (institutionDepartment: string) => {
        const id = getIdParams(institutionDepartment);
        onView(can['view:institution-departments'], route('institution-departments.show', id));
    };

    const archiveDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onDelete(can['delete:institution-departments'], route('institution-departments.destroy', id), name);
    };

    const restoreDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onRestore(can['restore:institution-departments'], route('institution-departments.restore', id), name);
    };

    const deleteDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onForceDelete(can['forceDelete:institution-settings'], route('institution-departments.force-delete', id), name);
    };

    return {
        archiveDepartment,
        breadcrumbs,
        createInstitutionDepartmentColumns,
        deleteDepartment,
        openInstitutionDepartmentsModal,
        restoreDepartment,
        syncInstitutionDepartments,
        viewDepartment,
    };
};
