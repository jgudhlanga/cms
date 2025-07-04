import { useDataTables } from '@/composables/core/useDataTables';
import { getIdParams } from '@/lib/utils';

import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { useUtils } from '@/composables/core/useUtils';
import { errorAlert } from '@/lib/alerts';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import HttpService from '@/services/http.service';
import { useStaffCreateFormStore, useStaffDataStore } from '@/store/institution/useStaffStore';
import { Role } from '@/types/acl';
import { ApiFilterResponse } from '@/types/data-pagination';
import { Staff } from '@/types/staff';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { ZodObject } from 'zod';

export const useStaff = () => {
    const { moreActionButton, avatar } = useDataTables();
    const { navigateTo } = useUtils();
    const createStaffColumns = () => {
        return [
            {
                header: trans('trans.staff'),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Staff } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return avatar({
                        href: route('staff.show', id),
                        title: row.original.relationships?.user?.attributes?.name ?? '',
                        src: row.original.relationships?.user?.attributes?.avatarUrl ?? '',
                        classes: 'size-8 border-2 border-primary rounded-full',
                    });
                },
            },
            {
                header: trans('trans.email'),
                accessorKey: 'email',
                cell: ({ row }: { row: { original: Staff } }) => {
                    return row.original.relationships?.user?.attributes?.email ?? '---';
                },
            },
            {
                header: trans_choice('trans.role', 2),
                accessorKey: 'roles',
                cell: ({ row }: { row: { original: Staff } }) => {
                    return row.original.relationships?.roles?.map((item: Role) => item?.attributes?.name)?.join(', ');
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
    const staff = ref<ApiFilterResponse | null>(null);
    const loadStaff = async (url: string) => {
        const staffDataStore = useStaffDataStore();
        try {
            isLoading.value = true;
            staff.value = await HttpService.get(url);
            staffDataStore.setStaff(staff.value as ApiFilterResponse);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.course', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const createFormSchema = (isNativeCitizen: boolean) => {
        const personal = [
            'firstNameSchema',
            'lastNameSchema',
            'genderSchema',
            'maritalStatusSchema',
            'dobSchema',
            'emailSchema',
            'employmentTypeSchema',
        ];
        if (isNativeCitizen) {
            personal.push('idNumberSchema');
        } else {
            personal.push('passportNumberSchema');
            personal.push('countrySchema');
        }
        return mergeValidationSchema(schemaFields)(personal, schemaFields['titleSchema']());
    };
    const getName = () => trans('trans.staff');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveStaff = (form: InertiaForm<any>, institutionDepartmentId: string) => {
        try {
            form.post(route('staff.store', institutionDepartmentId), buildFormOptions(form, successMessage(), errorMessage()));
            const store = useStaffCreateFormStore();
            store.$reset();
            store.$dispose();
            navigateTo(route('institution-departments.show', institutionDepartmentId));
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
        createFormSchema,
    };
};
