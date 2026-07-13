import { BaseCheckbox } from '@/components/core/form';
import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import {
    openMaintenancePurgeDialog,
    toPurgeDialogUser,
} from '@/composables/maintenance/useMaintenancePurgeDialog';
import { ColorVariant } from '@/enums/colors';
import { PAGINATION_ITEMS_PER_PAGE } from '@/lib/constants';
import { errorAlert, successAlert } from '@/lib/alerts';
import { buildStudentShowUrl, currentPageReturnPath } from '@/lib/studentShowNavigation';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type {
    MaintenanceUserBulkPurgeResult,
    MaintenanceUsersFiltersState,
    NonEnrolledStudentUser,
} from '@/types/maintenance-users';
import { usePage } from '@inertiajs/vue3';
import { trans, trans_choice } from 'laravel-vue-i18n';
import type { ComputedRef, Ref } from 'vue';
import { h, ref } from 'vue';

interface CreateMaintenanceUserColumnsOptions {
    selectedUserIds: Ref<number[]>;
    selectAllModel: ComputedRef<boolean>;
    onPurgeSuccess: () => void | Promise<void>;
}

export interface NonEnrolledStudentUsersPagination {
    page?: number;
    pageSize?: number;
}

export const parseNonEnrolledStudentUsersListUrl = (listUrl: string): MaintenanceUsersFiltersState => {
    const url = new URL(listUrl, window.location.origin);
    const applicationStatus = url.searchParams.get('application_status');

    return {
        search: url.searchParams.get('search') ?? undefined,
        applicationStatus: applicationStatus
            ? (applicationStatus as MaintenanceUsersFiltersState['applicationStatus'])
            : undefined,
    };
};

export const mergeMaintenanceUsersFiltersFromUrl = (
    currentFilters: MaintenanceUsersFiltersState,
    listUrl: string,
): MaintenanceUsersFiltersState => {
    const parsedUrl = new URL(listUrl, window.location.origin);
    const urlFilters = parseNonEnrolledStudentUsersListUrl(listUrl);

    return {
        applicationStatus: parsedUrl.searchParams.has('application_status')
            ? urlFilters.applicationStatus
            : currentFilters.applicationStatus,
        search: parsedUrl.searchParams.has('search') ? urlFilters.search : undefined,
    };
};

export const resolveNonEnrolledStudentUsersListPath = (
    filters: MaintenanceUsersFiltersState = {},
    paginatorUrl?: string,
    pagination: NonEnrolledStudentUsersPagination = {},
): string => {
    const parsed = new URL(
        paginatorUrl ?? route('maintenance.non-enrolled-student-users'),
        window.location.origin,
    );

    if (paginatorUrl) {
        if (!parsed.searchParams.has('application_status') && filters.applicationStatus) {
            parsed.searchParams.set('application_status', filters.applicationStatus);
        }

        if (!parsed.searchParams.has('search') && filters.search) {
            parsed.searchParams.set('search', filters.search);
        }

        return `${parsed.pathname}${parsed.search}`;
    }

    if (filters.search) {
        parsed.searchParams.set('search', filters.search);
    } else {
        parsed.searchParams.delete('search');
    }

    if (filters.applicationStatus) {
        parsed.searchParams.set('application_status', filters.applicationStatus);
    } else {
        parsed.searchParams.delete('application_status');
    }

    const page = pagination.page ?? 1;
    const pageSize = pagination.pageSize ?? PAGINATION_ITEMS_PER_PAGE;

    if (page > 1) {
        parsed.searchParams.set('page', String(page));
    } else {
        parsed.searchParams.delete('page');
    }

    parsed.searchParams.set('page_size', String(pageSize));

    return `${parsed.pathname}${parsed.search}`;
};

export const useMaintenanceUsers = () => {
    const { textLink, actionButton } = useDataTables();
    const { formatDate, navigateTo } = useUtils();
    const page = usePage();
    const isLoading = ref(false);
    const isPurging = ref(false);

    const purgeMaintenanceUser = async (userId: number, reason: string): Promise<void> => {
        await HttpService.delete(route('maintenance.non-enrolled-student-users.purge', userId), {
            data: { reason },
        });
    };

    const purgeMaintenanceUsersBulk = async (userIds: number[], reason: string): Promise<MaintenanceUserBulkPurgeResult> => {
        return await HttpService.post(route('maintenance.non-enrolled-student-users.bulk-purge'), {
            user_ids: userIds,
            reason,
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
        openMaintenancePurgeDialog([toPurgeDialogUser(user)], (reason) => {
            isPurging.value = true;
            void purgeMaintenanceUser(user.id, reason)
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
            (reason) => {
                isPurging.value = true;
                void purgeMaintenanceUsersBulk(userIds, reason)
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
                        onClick: () =>
                            navigateTo(
                                buildStudentShowUrl(studentId, {
                                    from: 'maintenance',
                                    return: currentPageReturnPath(page.url, window.location.origin),
                                }),
                            ),
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
        pagination: NonEnrolledStudentUsersPagination = {},
    ): Promise<ApiFilterResponse | undefined> => {
        try {
            isLoading.value = true;
            const path = resolveNonEnrolledStudentUsersListPath(filters, paginatorUrl, pagination);

            return await HttpService.get(path);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans_choice('trans.user', 2) }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        resolveNonEnrolledStudentUsersListPath,
        createMaintenanceUserColumns,
        fetchNonEnrolledStudentUsers,
        purgeEligibleUserIds,
        purgeEligibleUsers,
        handleBulkPurgeUsers,
        isLoading,
        isPurging,
    };
};
