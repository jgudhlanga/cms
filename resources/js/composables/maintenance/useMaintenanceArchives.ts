import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import {
    openArchiveFlushDialog,
    openArchiveRestoreDialog,
    toArchiveDialogTarget,
} from '@/composables/maintenance/useMaintenanceArchiveDialogs';
import { ColorVariant } from '@/enums/colors';
import { PAGINATION_ITEMS_PER_PAGE } from '@/lib/constants';
import { errorAlert, successAlert } from '@/lib/alerts';
import { getIdParams } from '@/lib/utils';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type {
    AccountPurgeArchive,
    AccountPurgeArchiveRestoreResponse,
    AccountPurgeArchiveStatus,
    MaintenanceArchivesFiltersState,
} from '@/types/maintenance-archives';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';

export interface MaintenanceArchivesPagination {
    page?: number;
    pageSize?: number;
}

export const parseMaintenanceArchivesListUrl = (listUrl: string): MaintenanceArchivesFiltersState => {
    const url = new URL(listUrl, window.location.origin);

    return {
        search: url.searchParams.get('search') ?? undefined,
        purgeType: (url.searchParams.get('purge_type') as MaintenanceArchivesFiltersState['purgeType']) ?? undefined,
        status: (url.searchParams.get('status') as MaintenanceArchivesFiltersState['status']) ?? undefined,
    };
};

export const mergeMaintenanceArchivesFiltersFromUrl = (
    currentFilters: MaintenanceArchivesFiltersState,
    listUrl: string,
): MaintenanceArchivesFiltersState => {
    const parsedUrl = new URL(listUrl, window.location.origin);
    const urlFilters = parseMaintenanceArchivesListUrl(listUrl);

    return {
        purgeType: parsedUrl.searchParams.has('purge_type') ? urlFilters.purgeType : currentFilters.purgeType,
        status: parsedUrl.searchParams.has('status') ? urlFilters.status : currentFilters.status,
        search: parsedUrl.searchParams.has('search') ? urlFilters.search : undefined,
    };
};

export const resolveMaintenanceArchivesListPath = (
    filters: MaintenanceArchivesFiltersState = {},
    paginatorUrl?: string,
    pagination: MaintenanceArchivesPagination = {},
): string => {
    const parsed = new URL(paginatorUrl ?? route('maintenance.account-purge-archives'), window.location.origin);

    if (paginatorUrl) {
        if (!parsed.searchParams.has('purge_type') && filters.purgeType && filters.purgeType !== 'all') {
            parsed.searchParams.set('purge_type', filters.purgeType);
        }

        if (!parsed.searchParams.has('status') && filters.status && filters.status !== 'all') {
            parsed.searchParams.set('status', filters.status);
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

    if (filters.purgeType && filters.purgeType !== 'all') {
        parsed.searchParams.set('purge_type', filters.purgeType);
    } else {
        parsed.searchParams.delete('purge_type');
    }

    if (filters.status && filters.status !== 'all') {
        parsed.searchParams.set('status', filters.status);
    } else {
        parsed.searchParams.delete('status');
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

const statusBadgeClass = (status: AccountPurgeArchiveStatus): string => {
    switch (status) {
        case 'active':
            return 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-100';
        case 'restored':
            return 'bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-100';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const truncateText = (value: string | null, maxLength = 60): string => {
    if (!value) {
        return '---';
    }

    if (value.length <= maxLength) {
        return value;
    }

    return `${value.slice(0, maxLength)}…`;
};

interface ArchiveActionError {
    response?: {
        data?: {
            message?: string;
        };
    };
}

export const useMaintenanceArchives = () => {
    const { actionButton, textLink } = useDataTables();
    const { formatDate, navigateTo } = useUtils();
    const isLoading = ref(false);
    const isRestoring = ref(false);
    const isFlushing = ref(false);

    const fetchAccountPurgeArchives = async (
        filters: MaintenanceArchivesFiltersState = {},
        paginatorUrl?: string,
        pagination: MaintenanceArchivesPagination = {},
    ): Promise<ApiFilterResponse | null> => {
        isLoading.value = true;

        try {
            const listPath = resolveMaintenanceArchivesListPath(filters, paginatorUrl, pagination);

            return (await HttpService.get(listPath)) as ApiFilterResponse;
        } catch {
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const restoreArchive = async (archiveId: number): Promise<AccountPurgeArchiveRestoreResponse | null> => {
        try {
            return (await HttpService.post(
                route('maintenance.account-purge-archives.restore', archiveId),
            )) as AccountPurgeArchiveRestoreResponse;
        } catch (error) {
            const message =
                (error as ArchiveActionError).response?.data?.message ??
                trans('trans.maintenance_archives_restore_failure');
            errorAlert(message);

            return null;
        }
    };

    const flushArchive = async (archiveId: number): Promise<boolean> => {
        try {
            await HttpService.delete(route('maintenance.account-purge-archives.flush', archiveId));

            return true;
        } catch (error) {
            const message =
                (error as ArchiveActionError).response?.data?.message ??
                trans('trans.maintenance_archives_flush_failure');
            errorAlert(message);

            return false;
        }
    };

    const handleRestoreArchive = (
        archive: AccountPurgeArchive,
        onSuccess: () => void | Promise<void>,
    ): void => {
        openArchiveRestoreDialog(toArchiveDialogTarget(archive), () => {
            isRestoring.value = true;
            void restoreArchive(archive.id)
                .then(async (result) => {
                    if (!result) {
                        return;
                    }

                    successAlert(trans('trans.maintenance_archives_restore_success'));
                    await onSuccess();

                    if (result.data.studentProfileUrl) {
                        navigateTo(result.data.studentProfileUrl);
                    } else if (result.data.userProfileUrl) {
                        navigateTo(result.data.userProfileUrl);
                    }
                })
                .finally(() => {
                    isRestoring.value = false;
                });
        });
    };

    const handleFlushArchive = (
        archive: AccountPurgeArchive,
        onSuccess: () => void | Promise<void>,
    ): void => {
        openArchiveFlushDialog(toArchiveDialogTarget(archive), () => {
            isFlushing.value = true;
            void flushArchive(archive.id)
                .then(async (success) => {
                    if (!success) {
                        return;
                    }

                    successAlert(trans('trans.maintenance_archives_flush_success'));
                    await onSuccess();
                })
                .finally(() => {
                    isFlushing.value = false;
                });
        });
    };

    const createMaintenanceArchiveColumns = (onActionSuccess: () => void | Promise<void>) => [
        {
            header: trans_choice('trans.name', 1),
            accessorKey: 'name',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => {
                const { attributes } = row.original;

                if (attributes.restoredAt && attributes.originalStudentId) {
                    return textLink(
                        route('students.show', getIdParams(String(attributes.originalStudentId))),
                        attributes.name ?? '---',
                    );
                }

                if (attributes.restoredAt && attributes.originalUserId) {
                    return textLink(
                        route('users.show', getIdParams(String(attributes.originalUserId))),
                        attributes.name ?? '---',
                    );
                }

                return attributes.name ?? '---';
            },
        },
        {
            header: trans('trans.email_address'),
            accessorKey: 'attributes.email',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => row.original.attributes.email ?? '---',
        },
        {
            header: trans_choice('trans.student_number', 1),
            accessorKey: 'attributes.studentNumber',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) =>
                row.original.attributes.studentNumber ?? '---',
        },
        {
            header: trans('trans.type'),
            accessorKey: 'attributes.purgeTypeLabel',
        },
        {
            header: trans('trans.status'),
            accessorKey: 'attributes.status',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => {
                const status = row.original.attributes.status;

                return h(
                    'span',
                    {
                        class: `inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase ${statusBadgeClass(status)}`,
                    },
                    row.original.attributes.statusLabel,
                );
            },
        },
        {
            header: trans('trans.maintenance_archives_column_purged_by'),
            accessorKey: 'attributes.purgedByName',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) =>
                row.original.attributes.purgedByName ?? '---',
        },
        {
            header: trans('trans.purged_at'),
            accessorKey: 'attributes.purgedAt',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) =>
                row.original.attributes.purgedAt
                    ? formatDate(row.original.attributes.purgedAt, 'LL')
                    : '---',
        },
        {
            header: trans('trans.maintenance_archives_column_deletes_in'),
            accessorKey: 'attributes.daysUntilFlush',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => {
                const days = row.original.attributes.daysUntilFlush;

                if (days === null) {
                    return '---';
                }

                if (days === 0) {
                    return h(
                        'span',
                        { class: 'font-medium text-red-600' },
                        trans('trans.maintenance_archives_days_remaining_today'),
                    );
                }

                const label = trans_choice('trans.maintenance_archives_days_remaining', days, {
                    count: days,
                });

                return h(
                    'span',
                    {
                        class: days <= 7 ? 'font-medium text-amber-700' : undefined,
                    },
                    label,
                );
            },
        },
        {
            header: trans('trans.maintenance_archives_column_purge_reason'),
            accessorKey: 'attributes.purgeReason',
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => {
                const reason = row.original.attributes.purgeReason;

                return h(
                    'span',
                    {
                        title: reason ?? undefined,
                        class: 'block max-w-xs truncate',
                    },
                    truncateText(reason),
                );
            },
        },
        {
            header: trans_choice('trans.action', 2),
            accessorKey: 'actions',
            enableSorting: false,
            meta: { align: 'right' },
            cell: ({ row }: { row: { original: AccountPurgeArchive } }) => {
                const archive = row.original;
                const buttons = [];

                if (archive.attributes.canRestore) {
                    buttons.push(
                        actionButton({
                            title: trans('trans.maintenance_archives_restore'),
                            onClick: () => handleRestoreArchive(archive, onActionSuccess),
                            variant: ColorVariant.success_outline,
                        }),
                    );
                }

                if (archive.attributes.canFlush) {
                    buttons.push(
                        actionButton({
                            title: trans('trans.maintenance_archives_delete'),
                            onClick: () => handleFlushArchive(archive, onActionSuccess),
                            variant: ColorVariant.danger_outline,
                        }),
                    );
                }

                if (buttons.length === 0) {
                    return '---';
                }

                return h('div', { class: 'flex flex-wrap justify-end gap-2' }, buttons);
            },
        },
    ];

    return {
        createMaintenanceArchiveColumns,
        fetchAccountPurgeArchives,
        isLoading,
        isRestoring,
        isFlushing,
    };
};
