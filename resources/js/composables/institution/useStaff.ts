import { useDataTables } from '@/composables/core/useDataTables';
import { getIdParams } from '@/lib/utils';

import { errorAlert } from '@/lib/alerts';
import { buildFormOptions } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { useStaffStore } from '@/store/institution/useStaffStore';
import { Staff, StaffSearchData } from '@/types/staff';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useStaff = () => {
    const { moreActionButton, textLink } = useDataTables();

    const createStaffColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Staff } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('staff.show', id), row.original.relationships?.user?.attributes?.name ?? '');
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Staff } }) => {
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => {},
                        },
                        {
                            key: 'edit',
                            action: () => {},
                        },
                    ]);
                },
            },
        ];
    };

    const isLoading = ref(false);
    const staff = ref<StaffSearchData | null>(null);
    const loadStaff = async (institutionDepartmentId: string) => {
        try {
            isLoading.value = true;
            staff.value = await HttpService.get(route('v1.department-metadata.staff', institutionDepartmentId));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.course', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const getName = () => trans('trans.staff');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveStaff = (form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            form.post(route('staff.store', institutionDepartmentId), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useStaffStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    return {
        createStaffColumns,
        loadStaff,
        staff,
        isLoading,
        saveStaff,
    };
};
