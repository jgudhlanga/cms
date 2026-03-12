import { useDataTables } from '@/composables/core/useDataTables';
import { getIdParams } from '@/lib/utils';

import BasicInfo from '@/components/staff/BasicInfo.vue';
import StaffAddresses from '@/components/staff/StaffAddresses.vue';
import StaffContacts from '@/components/staff/StaffContacts.vue';
import Documents from '@/components/staff/Documents.vue';
import ProfessionalInfo from '@/components/staff/ProfessionalInfo.vue';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, forbiddenAlert } from '@/lib/alerts';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { emailUniqueSchema, employeeNumberUniqueSchema, phoneNumberUniqueSchema } from '@/lib/uniqueValidations';
import HttpService from '@/services/http.service';
import { useStaffCreateFormStore, useStaffDataStore } from '@/store/institution/useStaffStore';
import { Role } from '@/types/acl';
import { ApiFilterResponse } from '@/types/data-pagination';
import { Staff } from '@/types/staff';
import { CustomTab } from '@/types/utils';
import { InertiaForm } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { ZodObject } from 'zod';

export const useStaff = () => {
    const { moreActionButton, avatar, onView, tag } = useDataTables();
    const { formatDate, navigateTo } = useUtils();
    const createStaffColumns = (institutionDepartmentId: string) => {
        return [
            {
                header: trans('trans.staff'),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: Staff } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const title = row.original.attributes?.title ?? '';
                    return hasAbility('view:department-metadata')
                        ? avatar({
                              href: route('staff.show', { department: institutionDepartmentId, staff: id }),
                              title: `${title} ${row.original.relationships?.user?.attributes?.name ?? ''}`,
                              src: row.original.relationships?.user?.attributes?.avatarUrl ?? '',
                              classes: 'size-8 rounded-full',
                          })
                        : `${title} ${row.original.relationships?.user?.attributes?.name ?? ''}`;
                },
            },
            {
                header: trans('trans.employee_number'),
                accessorKey: 'employee_number',
                cell: ({ row }: { row: { original: Staff } }) => {
                    return tag(row.original.attributes?.employeeNumber ?? '---', 'py-0.5', ColorVariant.primary);
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
                header: trans('trans.phone_number'),
                accessorKey: 'phoneNumber',
                cell: ({ row }: { row: { original: Staff } }) => {
                    return row.original.relationships?.user?.attributes?.phoneNumber ?? '---';
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
                header: trans('trans.login_count'),
                accessorKey: 'loginCount',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: Staff } }) => {
                    return row.original.relationships?.user?.attributes?.loginCount ?? '---';
                },
            },
            {
                header: trans('trans.last_login'),
                accessorKey: 'lastLoginAt',
                cell: ({ row }: { row: { original: Staff } }) => {
                    const loginDate = row.original?.relationships?.user?.attributes?.lastLoginAt ?? '';
                    return loginDate ? formatDate(loginDate, 'LL') : '---';
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Staff } }) => {
                    const allowed = hasAbility('view:department-metadata');
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () =>
                                onView(
                                    allowed,
                                    route('staff.show', {
                                        department: institutionDepartmentId,
                                        staff: id,
                                    }),
                                ),
                        },
                        {
                            key: 'edit',
                            action: () =>
                                hasAbility('update:department-metadata')
                                    ? navigateTo(route('staff.edit', { department: institutionDepartmentId, staff: id }))
                                    : forbiddenAlert(),
                        },
                    ]);
                },
            },
        ];
    };

    const isLoading = ref(false);
    const departmentStaff = ref<ApiFilterResponse | null>(null);
    const loadDepartmentStaff = async (url: string) => {
        const staffDataStore = useStaffDataStore();
        try {
            isLoading.value = true;
            departmentStaff.value = await HttpService.get(url);
            staffDataStore.setStaff(departmentStaff.value as ApiFilterResponse);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.course', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const validateFormSchema = (staff?: Staff) => {
        const personal = ['firstNameSchema', 'lastNameSchema', 'genderSchema', 'maritalStatusSchema', 'dobSchema', 'employmentTypeSchema'];
        return mergeValidationSchema(schemaFields)(
            personal,
            schemaFields['titleSchema']()
                .merge(emailUniqueSchema(`api/v1/validations/check?current_id=${staff?.attributes?.userId}&key=user_email&value=`))
                .merge(phoneNumberUniqueSchema(`api/v1/validations/check?current_id=${staff?.attributes?.userId}&key=user_phone_number&value=`))
                .merge(employeeNumberUniqueSchema(`api/v1/validations/check?current_id=${staff?.id}&key=staff_employee_number&value=`)),
        );
    };

    const getName = () => trans('trans.staff');
    const successMessage = () => trans('trans.item_saved', { item: getName() });
    const errorMessage = () => trans('trans.item_save_failure', { item: getName() });
    const saveStaff = (form: InertiaForm<any>, institutionDepartmentId: string, staffId?: string) => {
        try {
            if (Number(staffId) > 0) {
                form.put(
                    route('staff.update', { department: institutionDepartmentId, staff: staffId }),
                    buildFormOptions(form, successMessage(), errorMessage()),
                );
            } else {
                form.post(route('staff.store', institutionDepartmentId), buildFormOptions(form, successMessage(), errorMessage()));
            }
            const store = useStaffCreateFormStore();
            store.$reset();
            store.$dispose();
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const staff = ref<ApiFilterResponse | null>(null);
    const loadStaff = async (url: string) => {
        try {
            isLoading.value = true;
            staff.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.staff', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const staffTabs = (staff: Staff, institutionDepartmentId: string): CustomTab[] => {
        return [
            {
                transLabel: () => trans('trans.basic_info'),
                value: 'basic_info',
                component: h(BasicInfo, { staff, institutionDepartmentId }),
                icon: IconName.user,
            },
            {
                transLabel: () => trans('trans.professional_info'),
                value: 'professional_info',
                component: h(ProfessionalInfo),
                icon: IconName.briefcase,
            },
            {
                transLabel: () => trans_choice('trans.contact', 2),
                value: 'contacts',
                component: h(StaffContacts),
                icon: IconName.contact,
            },
            {
                transLabel: () => trans_choice('trans.address', 2),
                value: 'addresses',
                component: h(StaffAddresses),
                icon: IconName.address,
            },
            {
                transLabel: () => trans_choice('trans.document', 2),
                value: 'documents',
                component: h(Documents),
                icon: IconName.file_search,
            },
        ];
    };
    const getStaffData = async (url: string) => {
        try {
            isLoading.value = true;
            return await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.portal_info') }));
        } finally {
            isLoading.value = false;
        }
    };
    return {
        createStaffColumns,
        loadDepartmentStaff,
        departmentStaff,
        isLoading,
        saveStaff,
        validateFormSchema,
        loadStaff,
        staff,
        staffTabs,
        getStaffData,
    };
};
