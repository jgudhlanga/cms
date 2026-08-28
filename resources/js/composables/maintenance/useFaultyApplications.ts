import { useDataTables } from '@/composables/core/useDataTables';
import { errorAlert } from '@/lib/alerts';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
import HttpService from '@/services/http.service';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type {
    FaultyApplication,
    FaultyApplicationReason,
    FaultyApplicationsFiltersState,
} from '@/types/faulty-applications';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';

const reasonLabel = (reason: FaultyApplicationReason): string =>
    trans(`trans.maintenance_faulty_applications_reason_${reason}`);

export const useFaultyApplications = () => {
    const { textLink } = useDataTables();
    const isLoading = ref(false);

    const textCell = (value: string | null | undefined) => h('span', { class: 'text-sm' }, value || '---');

    const createFaultyApplicationColumns = () => [
        {
            header: trans_choice('trans.name', 1),
            accessorKey: 'attributes.name',
            cell: ({ row }: { row: { original: FaultyApplication } }) => {
                const application = row.original;
                const studentId = application.attributes.studentId;

                if (!studentId) {
                    return textCell(application.attributes.name);
                }

                return textLink(
                    buildStudentShowUrl(studentId, {
                        from: 'maintenance',
                        return: route('maintenance.faulty-applications'),
                    }),
                    application.attributes.name ?? '---',
                );
            },
        },
        {
            header: trans_choice('trans.student_number', 1),
            accessorKey: 'attributes.studentNumber',
            cell: ({ row }: { row: { original: FaultyApplication } }) =>
                h('span', { class: 'font-mono text-xs' }, row.original.attributes.studentNumber || '---'),
        },
        {
            header: trans('trans.maintenance_faulty_applications_tracking_number'),
            accessorKey: 'attributes.trackingNumber',
            cell: ({ row }: { row: { original: FaultyApplication } }) =>
                h('span', { class: 'font-mono text-xs' }, row.original.attributes.trackingNumber || '---'),
        },
        {
            header: trans_choice('trans.department', 1),
            accessorKey: 'attributes.department',
            cell: ({ row }: { row: { original: FaultyApplication } }) => textCell(row.original.attributes.department),
        },
        {
            header: trans_choice('trans.level', 1),
            accessorKey: 'attributes.level',
            cell: ({ row }: { row: { original: FaultyApplication } }) => textCell(row.original.attributes.level),
        },
        {
            header: trans_choice('trans.course', 1),
            accessorKey: 'attributes.course',
            cell: ({ row }: { row: { original: FaultyApplication } }) => textCell(row.original.attributes.course),
        },
        {
            header: trans_choice('trans.status', 1),
            accessorKey: 'attributes.applicationStatus',
            cell: ({ row }: { row: { original: FaultyApplication } }) =>
                textCell(row.original.attributes.applicationStatus),
        },
        {
            header: trans('trans.maintenance_faulty_applications_reasons'),
            accessorKey: 'attributes.reasons',
            enableSorting: false,
            cell: ({ row }: { row: { original: FaultyApplication } }) =>
                h(
                    'div',
                    { class: 'flex flex-wrap gap-1' },
                    row.original.attributes.reasons.map((reason) =>
                        h(
                            'span',
                            {
                                key: reason,
                                class: 'inline-flex rounded-full bg-destructive/10 px-2 py-0.5 text-[10px] font-semibold uppercase text-destructive',
                            },
                            reasonLabel(reason),
                        ),
                    ),
                ),
        },
    ];

    const fetchFaultyApplications = async (
        filters: FaultyApplicationsFiltersState = {},
        paginatorUrl?: string,
    ): Promise<ApiFilterResponse | undefined> => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('maintenance.faulty-applications.data');
            const url = new URL(baseUrl, window.location.origin);

            if (filters.search) {
                url.searchParams.set('search', filters.search);
            }

            return await HttpService.get(url.pathname + url.search);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.maintenance_faulty_applications') }));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        createFaultyApplicationColumns,
        fetchFaultyApplications,
        isLoading,
    };
};
