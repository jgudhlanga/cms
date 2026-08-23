import { useDataTables } from '@/composables/core/useDataTables';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { hasAbility } from '@/lib/permissions';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { ApiFilterResponse } from '@/types/data-pagination';
import { InstitutionDepartment } from '@/types/institution';
import TableRowExpandToggle from '@/components/core/table/TableRowExpandToggle.vue';
import InstitutionDepartmentNameCell from '@/components/institution/InstitutionDepartmentNameCell.vue';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { Ref, h, ref } from 'vue';

type InstitutionDepartmentColumnOptions = {
    expandedRowId?: Ref<string | null>;
    onToggleExpand?: (id: string) => void;
};

export const useInstitutionDepartments = () => {
    const { onDelete, onForceDelete, onRestore, onView } = useDataTables();

    const institutionDepartmentRowKey = (department: InstitutionDepartment): string => String(department.id ?? '');

    const createInstitutionDepartmentColumns = (options: InstitutionDepartmentColumnOptions = {}) => {
        return [
            {
                header: trans_choice('trans.department', 1),
                accessorKey: 'department',
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    return h(InstitutionDepartmentNameCell, {
                        departmentName: row.original.attributes?.department ?? '',
                        colorCode: row.original.attributes?.colorCode,
                    });
                },
            },
            {
                header: trans_choice('trans.code', 1),
                accessorKey: 'departmentCode',
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    return row.original?.attributes?.departmentCode ?? '';
                },
            },
            {
                id: 'expand',
                header: '',
                enableSorting: false,
                enableHiding: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: InstitutionDepartment } }) => {
                    const id = institutionDepartmentRowKey(row.original);

                    return h(TableRowExpandToggle, {
                        expanded: options.expandedRowId?.value === id,
                        onToggle: () => options.onToggleExpand?.(id),
                    });
                },
            },
        ];
    };

    const openDepartmentDivisionModal = (department: InstitutionDepartment) => {
        if (!hasAbility('update:department-metadata')) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.institution_department_division, edit: department });
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
        institutionDepartmentRowKey,
        openInstitutionDepartmentsModal,
        openDepartmentDivisionModal,
        restoreDepartment,
        syncInstitutionDepartments,
        viewDepartment,
        isLoading,
        departments,
        listDepartments,
    };
};
