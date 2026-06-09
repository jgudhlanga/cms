import { BaseCheckbox } from '@/components/core/form';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import {
    openMaintenancePurgeDialog,
    toPurgeDialogUser,
} from '@/composables/maintenance/useMaintenancePurgeDialog';
import { ColorVariant } from '@/enums/colors';
import { errorAlert, successAlert } from '@/lib/alerts';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type {
    MaintenanceUserBulkPurgeResult,
    MaintenanceUsersFiltersState,
    NonEnrolledStudentUser,
} from '@/types/maintenance-users';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';
import { h, ref } from 'vue';

interface CreateMaintenanceUserColumnsOptions {
    selectedUserIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    onPurgeSuccess: () => void | Promise<void>;
}

export const useMaintenanceUsers = () => {
    const { textLink, actionButton } = useDataTables();
    const { formatDate, navigateTo } = useUtils();
    const isLoading = ref(false);
    const isPurging = ref(false);

    const purgeMaintenanceUser = async (userId: number): Promise<void> => {
        await HttpService.delete(route('maintenance.non-enrolled-student-users.purge', userId));
    };

    const purgeMaintenanceUsersBulk = async (userIds: number[]): Promise<MaintenanceUserBulkPurgeResult> => {
        return await HttpService.post(route('maintenance.non-enrolled-student-users.bulk-purge'), {
            user_ids: userIds,
        });
    };

    const purgeEligibleUserIds = (users: NonEnrolledStudentUser[], selectedUserIds: number[]): number[] => {
        const eligibleIds = new Set(
            users.filter((user) => !user.attributes.hasStudentProfile).map((user) => user.id),
        );

        return selectedUserIds.filter((id) => eligibleIds.has(id));
    };

    const purgeEligibleUsers = (
        users: NonEnrolledStudentUser[],
        selectedUserIds: number[],
    ): NonEnrolledStudentUser[] => {
        const eligibleIds = new Set(purgeEligibleUserIds(users, selectedUserIds));

        return users.filter((user) => eligibleIds.has(user.id));
    };

    const handlePurgeUser = (user: NonEnrolledStudentUser, onPurgeSuccess: () => void | Promise<void>) => {
        openMaintenancePurgeDialog([toPurgeDialogUser(user)], () => {
            isPurging.value = true;
            void purgeMaintenanceUser(user.id)
                .then(async () => {
                    successAlert(trans('trans.maintenance_users_purge_success'));
                    await onPurgeSuccess();
                })
                .catch(() => errorAlert(trans('trans.maintenance_users_purge_failure')))
                .finally(() => {
                    isPurging.value = false;
                });
        });
    };

    const handleBulkPurgeUsers = (
        users: NonEnrolledStudentUser[],
        onPurgeSuccess: () => void | Promise<void>,
    ) => {
        if (users.length === 0) {
            return;
        }

        const userIds = users.map((user) => user.id);

        openMaintenancePurgeDialog(
            users.map(toPurgeDialogUser),
            () => {
                isPurging.value = true;
                void purgeMaintenanceUsersBulk(userIds)
                    .then(async (result) => {
                        if (result.purged.length > 0) {
                            successAlert(
                                trans('trans.maintenance_users_bulk_purge_success', {
                                    count: result.purged.length,
                                }),
                            );
                            await onPurgeSuccess();
                            return;
                        }

                        errorAlert(trans('trans.maintenance_users_purge_failure'));
                    })
                    .catch(() => errorAlert(trans('trans.maintenance_users_purge_failure')))
                    .finally(() => {
                        isPurging.value = false;
                    });
            },
        );
    };

    const createMaintenanceUserColumns = ({
        selectedUserIds,
        selectAllModel,
        onPurgeSuccess,
    }: CreateMaintenanceUserColumnsOptions) => [
        {
            header: () =>
                h(BaseCheckbox, {
                    inputId: 'select_all_maintenance_users',
                    label: '',
                    modelValue: selectAllModel.value,
                    'onUpdate:modelValue': (value: boolean) => {
                        selectAllModel.value = value;
                    },
                }),
            accessorKey: 'select',
            enableSorting: false,
            meta: { align: 'center' },
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) =>
                h(BaseCheckbox, {
                    inputId: `select_maintenance_user_${row.original.id}`,
                    label: '',
                    modelValue: selectedUserIds.value,
                    'onUpdate:modelValue': (value: number[]) => {
                        selectedUserIds.value = value;
                    },
                    value: row.original.id,
                    disabled: row.original.attributes.hasStudentProfile,
                }),
        },
        {
            header: trans_choice('trans.name', 1),
            accessorKey: 'name',
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) => {
                const id = getIdParams(row.original.id?.toString() ?? '');
                return textLink(route('users.show', id), row.original.attributes.name);
            },
        },
        {
            header: trans('trans.email_address'),
            accessorKey: 'attributes.email',
        },
        {
            header: trans('trans.phone_number'),
            accessorKey: 'attributes.phoneNumber',
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) =>
                row.original.attributes.phoneNumber ?? '---',
        },
        {
            header: trans_choice('trans.role', 2),
            accessorKey: 'attributes.roles',
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) =>
                row.original.attributes.roles?.map((role) => role.name).join(', ') || '---',
        },
        {
            header: trans('trans.maintenance_users_status_column'),
            accessorKey: 'attributes.applicationStatusSummary',
        },
        {
            header: trans('trans.last_login'),
            accessorKey: 'attributes.lastLoginAt',
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) => {
                const loginDate = row.original.attributes.lastLoginAt;
                return loginDate ? formatDate(loginDate, 'LL') : '---';
            },
        },
        {
            header: trans('trans.created_at'),
            accessorKey: 'attributes.createdAt',
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) =>
                row.original.attributes.createdAt
                    ? formatDate(row.original.attributes.createdAt, 'LL')
                    : '---',
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }: { row: { original: NonEnrolledStudentUser } }) => {
                const studentId = row.original.attributes.studentId;

                if (row.original.attributes.hasStudentProfile && studentId !== null) {
                    return actionButton({
                        title: trans_choice('trans.profile', 1),
                        onClick: () => navigateTo(route('students.show', String(studentId))),
                        variant: ColorVariant.success,
                    });
                }

                if (!row.original.attributes.hasStudentProfile) {
                    return actionButton({
                        title: trans('trans.maintenance_users_purge'),
                        onClick: () => handlePurgeUser(row.original, onPurgeSuccess),
                        variant: ColorVariant.danger,
                    });
                }

                return null;
            },
        },
    ];

    const fetchNonEnrolledStudentUsers = async (
        filters: MaintenanceUsersFiltersState = {},
        paginatorUrl?: string,
    ): Promise<ApiFilterResponse | undefined> => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('maintenance.non-enrolled-student-users');
            const url = new URL(baseUrl, window.location.origin);

            if (filters.search) {
                url.searchParams.set('search', filters.search);
            }

            return await HttpService.get(url.pathname + url.search);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.user', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        createMaintenanceUserColumns,
        fetchNonEnrolledStudentUsers,
        purgeEligibleUserIds,
        purgeEligibleUsers,
        handleBulkPurgeUsers,
        isLoading,
        isPurging,
    };
};
