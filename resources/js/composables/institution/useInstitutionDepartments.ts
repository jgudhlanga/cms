import { useDataTables } from '@/composables/core/useDataTables';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { ApiFilterResponse } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useInstitutionDepartments = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, onView, textLink, textEditLink } = useDataTables();
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
            {
                header: trans_choice('trans.code', 1),
                accessorKey: 'departmentCode',
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textEditLink(row.original?.attributes?.departmentCode ?? '', () => {console.log('Edit department code')});
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    const id = row.original.id?.toString() ?? '';
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => viewDepartment(id),
                        },
                    ]);
                },
            },
        ];
    };

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
        if (!hasAbility('create:department-metadata')) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.institution_departments, edit: institutionDepartments });
    };

    const viewDepartment = (institutionDepartment: string) => {
        const id = getIdParams(institutionDepartment);
        onView(hasAbility('view:department-metadata'), route('institution-departments.show', id));
    };

    const archiveDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onDelete(hasAbility('delete:department-metadata'), route('institution-departments.destroy', id), name);
    };

    const restoreDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onRestore(hasAbility('restore:department-metadata'), route('institution-departments.restore', id), name);
    };

    const deleteDepartment = (institutionDepartment: string, name: string) => {
        const id = getIdParams(institutionDepartment);
        onForceDelete(hasAbility('forceDelete:department-metadata'), route('institution-departments.force-delete', id), name);
    };

    const isLoading = ref(false);
    const departments = ref<ApiFilterResponse | null>(null);
    const listDepartments = async (url: string) => {
        try {
            isLoading.value = true;
            departments.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.department', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        archiveDepartment,
        createInstitutionDepartmentColumns,
        deleteDepartment,
        openInstitutionDepartmentsModal,
        restoreDepartment,
        syncInstitutionDepartments,
        viewDepartment,
        isLoading,
        departments,
        listDepartments,
    };
};
