import { useDataTables } from '@/composables/core/useDataTables';
import { useSharedFormSchema } from '@/composables/core/useSharedFormSchema';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import { buildFormOptions, mergeValidationSchema } from '@/lib/forms';
import {
    emailUniqueSchema,
    employeeNumberUniqueSchema,
    idNumberUniqueSchema,
    passportNumberUniqueSchema,
    phoneNumberUniqueSchema,
} from '@/lib/uniqueValidations';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import { Auth, PageProps } from '@/types';
import { ApiFilterResponse } from '@/types/data-pagination';
import type { Link } from '@/types/ui';
import { User } from '@/types/users';
import { InertiaForm, usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';
import { z, ZodObject } from 'zod';

export const useUsers = () => {
    const { moreActionButton, onDelete, onForceDelete, onRestore, textLink, onView, actionButton } = useDataTables();
    const createUserColumns = () => {
        const { props } = usePage();
        const { formatDate, navigateTo } = useUtils();
        const { can } = props?.auth as Auth;
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'name',
                cell: ({ row }: { row: { original: User } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return textLink(route('users.show', id), row.original.attributes?.name);
                },
            },
            { header: trans('trans.id_number'), accessorKey: 'attributes.idNumber' },
            { header: trans('trans.email_address'), accessorKey: 'attributes.email' },
            { header: trans('trans.phone_number'), accessorKey: 'attributes.phoneNumber' },
            { header: trans('trans.login_count'), accessorKey: 'attributes.loginCount', meta: { align: 'center' } },
            {
                header: trans('trans.last_login'),
                accessorKey: 'lastLoginAt',
                cell: ({ row }: { row: { original: User } }) => {
                    const loginDate = row.original?.attributes?.lastLoginAt ?? '';
                    return loginDate ? formatDate(loginDate, 'LL') : '---';
                },
            },
            {
                header: 'Impersonate',
                accessorKey: 'canImpersonate',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: User } }) => {
                    const page = usePage<PageProps>();
                    const canImpersonate = page.props.auth.user.attributes.canImpersonate;
                    const isImpersonating = page.props.auth.impersonating;
                    const canBeImpersonated = row.original?.attributes?.canBeImpersonated ?? false;
                    return canImpersonate && canBeImpersonated
                        ? actionButton({
                              title: isImpersonating ? 'Switch Impersonation' : 'Impersonate',
                              onClick: () => navigateTo(route('impersonate', { id: row.original.id })),
                              variant: ColorVariant.success,
                          })
                        : null;
                },
            },
            {
                header: trans_choice('trans.student', 1),
                accessorKey: 'student',
                enableSorting: false,
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: User } }) => {
                    return hasStudentRole(row.original)
                        ? actionButton({
                              title: trans_choice('trans.profile', 1),
                              onClick: () => navigateTo(route('students.profile', { id: row.original.id })),
                              variant: ColorVariant.primary,
                          })
                        : null;
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: User } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    const name = trans_choice('trans.user', 1);
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        {
                            key: 'view',
                            action: () => onView(can['view:users'], route('users.show', id)),
                        },
                        {
                            key: 'edit',
                            action: () => navigateTo(route('users.edit', id)),
                        },
                        {
                            key: 'archive',
                            action: () => onDelete(can['delete:users'], route('users.destroy', id), name),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(can['restore:users'], route('users.restore', id), name),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(can['forceDelete:users'], route('users.force-delete', id), name),
                        },
                    ]);
                },
            },
        ];
    };

    const breadcrumbs: Array<Link> = [{ transChoiceKey: 'user' }];

    const schemaFields = useSharedFormSchema() as Record<string, () => ZodObject<any, any>>;
    const validateFormSchema = (userId?: string, staffId?: string) => {
        const personal = [
            'firstNameSchema',
            'lastNameSchema',
            'genderSchema',
            'maritalStatusSchema',
            'dobSchema',
            'employmentTypeSchema',
            'departmentSchema',
            'phoneNumberSchema',
        ];
        return mergeValidationSchema(schemaFields)(
            personal,
            schemaFields['titleSchema']()
                .merge(emailUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_email&value=`))
                .merge(phoneNumberUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_phone_number&value=`))
                .merge(employeeNumberUniqueSchema(`api/v1/validations/check?current_id=${staffId}&key=staff_employee_number&value=`)),
        );
    };

    const updateStudentUserSchema = (isNativeCitizen: boolean, userId: string, studentId: string) => {
        const personal = ['genderSchema', 'maritalStatusSchema', 'dobSchema', 'idTypeSchema', 'phoneNumberSchema'];
        let updateSchema = null;
        if (isNativeCitizen) {
            updateSchema = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(idNumberUniqueSchema(`api/v1/validations/check?current_id=${studentId}&key=student_national_id&value=`))
                    .merge(emailUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_email&value=`))
                    .merge(phoneNumberUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_phone_number&value=`)),
            );
        } else {
            personal.push('countrySchema');
            updateSchema = mergeValidationSchema(schemaFields)(
                personal,
                schemaFields['titleSchema']()
                    .merge(passportNumberUniqueSchema(`api/v1/validations/check?current_id=${studentId}&key=student_passport_number&value='`))
                    .merge(emailUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_email&value=`))
                    .merge(phoneNumberUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_phone_number&value=`)),
            );
        }
        return updateSchema;
    };

    const saveUser = (form: InertiaForm<any>, userId?: string) => {
        try {
            const success = trans('trans.item_saved', { item: trans_choice('trans.user', 1) });
            const error = trans('trans.item_save_failure', { item: trans_choice('trans.user', 1) });
            if (Number(userId) > 0) {
                form.put(route('users.update', userId), buildFormOptions(form, success, error));
            } else {
                form.post(route('users.store'), buildFormOptions(form, success, error));
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const saveStaffUser = (form: InertiaForm<any>, userId?: string) => {
        try {
            if (Number(userId) > 0) {
                updateStaffUser(form, String(userId));
            } else {
                createStaffUser(form);
            }
        } catch (error: any) {
            form.setError(error.format());
        }
    };

    const createStaffUser = (form: InertiaForm<any>) => {
        form.post(route('users.store-staff-user'), {
            onSuccess: () => {
                successAlert('User successfully created');
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, user could not be created');
                }
            },
        });
    };

    const updateStaffUser = (form: InertiaForm<any>, userId: string) => {
        form.put(route('users.update-staff-user', userId), {
            onSuccess: () => {
                successAlert('User successfully updated');
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, user could not be updated');
                }
            },
        });
    };

    const updateStudentUser = (form: InertiaForm<any>, userId: string) => {
        form.put(route('users.update-student-user', userId), {
            onSuccess: () => {
                successAlert('User successfully updated');
            },
            onError: (errors: any) => {
                if (Object.keys(errors).length) {
                    const allErrors = Object.values(errors).join('\n');
                    errorAlert(allErrors);
                } else {
                    errorAlert('An unexpected error happened, user could not be updated');
                }
            },
        }); 
    };

    const isValidating = ref(false);
    const updateUserCredentials = async (
        form: InertiaForm<any>,
        userId: string,
        options: { validateEmail?: boolean; validatePassword?: boolean } = {},
    ) => {
        const { validateEmail = true, validatePassword = true } = options;
        const formSchema = () => {
            const baseSchema = mergeValidationSchema(schemaFields)([], z.object({}));
            const passwordSchema = validatePassword
                ? mergeValidationSchema(schemaFields)(['passwordSchema'], schemaFields['passwordConfirmationSchema']())
                : baseSchema;

            return validateEmail
                ? passwordSchema.merge(emailUniqueSchema(`api/v1/validations/check?current_id=${userId}&key=user_email&value=`))
                : passwordSchema;
        };
        try {
            isValidating.value = true;
            await formSchema().parseAsync(form);
            form.put(
                route('users.update-user-credentials', {user: userId}),
                buildFormOptions(form, 'Authentication credentials successfully updated', 'An unexpected error happened, user credentals could not be updated', undefined, () => {
                    
                }),
            );
        }  catch (error: any) {
            form.setError(error.format());
        } finally {
            isValidating.value =false;
        }
    };

    const isLoading = ref(false);
    const users = ref<ApiFilterResponse | null>(null);
    const loadUsers = async (url: string) => {
        try {
            isLoading.value = true;
            users.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.user', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    const userPermissions = ref<ApiFilterResponse | null>(null);
    const loadUserPermissions = async (url: string) => {
        try {
            isLoading.value = true;
            userPermissions.value = await HttpService.get(url);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.permission', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    function hasStudentRole(user: User): boolean {
        return user.relationships.roles.some((role) => role.name === 'Student');
    }

    return {
        breadcrumbs,
        createUserColumns,
        saveUser,
        loadUsers,
        users,
        isLoading,
        validateFormSchema,
        saveStaffUser,
        updateStudentUser,
        updateStudentUserSchema,
        hasStudentRole,
        updateUserCredentials,
        isValidating,
        loadUserPermissions,
        userPermissions,
    };
};
