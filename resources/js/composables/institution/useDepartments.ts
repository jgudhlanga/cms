import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { errorAlert, forbiddenAlert, openModal } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { buildFormOptions } from '@/lib/forms';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth } from '@/types';
import { ApiFilterResponse } from '@/types/data-pagination';
import { Department } from '@/types/institution';
import type { Link } from '@/types/ui';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useDepartments = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, checkStatusIcon } = useDataTables();
    const createDepartmentColumns = () => {
        const { props } = usePage();
        const { can } = props?.auth as Auth;
        return [
            { header: trans_choice('#', 1), accessorKey: 'attributes.position', meta: { align: 'left' } },
            { header: trans_choice('trans.name', 1), accessorKey: 'attributes.name' },
            {
                header: trans('trans.is_academic'),
                accessorKey: 'isAcademic',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Department } }) => {
                    return checkStatusIcon(row.original?.attributes?.isAcademic);
                },
            },
            { header: trans_choice('trans.description', 1), accessorKey: 'attributes.description' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Department } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.department', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(can['update:institution-settings'], row.original) },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:institution-settings'], route('departments.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:institution-settings'], route('departments.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:institution-settings'], route('departments.force-delete', id), name),
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
        { transChoiceKey: 'department' },
    ];

    const saveDepartment = (form: InertiaForm<any>, department?: Department) => {
        const { nameSchema } = useSharedFormSchema();
        try {
            nameSchema().parse(form);
            const success = trans('trans.item_saved', { item: trans_choice('trans.department', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.department', 1) });
            if (department) {
                const id = getIdParams(department.id?.toString() ?? '');
                form.put(route('departments.update', id), buildFormOptions(form, success, error, APP_MODULE_KEYS.departments));
            } else {
                form.post(route('departments.store'), buildFormOptions(form, success, error, APP_MODULE_KEYS.departments));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };
    const onOpenModal = (can: boolean, department?: Department) => {
        if (!can) return forbiddenAlert();
        openModal({ name: APP_MODULE_KEYS.departments, edit: department });
    };

    /*    const listDepartments = async (search?: string) => {
            const { data, fetchData } = useDropdowns();
            isLoading.value = true;
            await fetchData({ url: 'api/v1/departments?page_size=all', search, transChoiceKey: 'trans.department' });
            isLoading.value = false;
            departments.value = data.value;
        };*/
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
        createDepartmentColumns,
        breadcrumbs,
        onOpenModal,
        saveDepartment,
        isLoading,
        departments,
        listDepartments,
    };
};
