import { useDataTables } from '@/composables/core/useDataTables';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import { errorAlert } from '@/lib/alerts';
import {
    buildJsonApiIndexParams,
    jsonApiRequestConfig,
    mergeJsonApiFiltersIntoRequestPath,
    parseJsonApiStudentIdCardRequests,
    toIdCardRequestJsonApiFilters,
} from '@/lib/json-api';
import { hasAbility } from '@/lib/permissions';
import { idCardRequestStatusTagVariant } from '@/lib/students/idCardRequestTagVariants';
import HttpService from '@/services/http.service';
import type { DataListProps } from '@/types/data-pagination';
import type { IdCardRequest, IdCardRequestFiltersState } from '@/types/id-cards';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { ref } from 'vue';

export const useIdCardRequests = () => {
    const { actionButton, tag, textLink } = useDataTables();
    const { formatDate, navigateTo } = useUtils();
    const isLoading = ref(false);

    const fetchIdCardRequests = async (
        filters: IdCardRequestFiltersState = {},
        paginatorUrl?: string,
        page?: { number?: number; size?: number },
    ): Promise<DataListProps<IdCardRequest> | undefined> => {
        try {
            isLoading.value = true;
            const jsonFilters = toIdCardRequestJsonApiFilters(filters);
            const path = paginatorUrl
                ? mergeJsonApiFiltersIntoRequestPath(paginatorUrl, jsonFilters)
                : route('v1.json.students.student-id-card-requests.index');

            const params = paginatorUrl ? undefined : buildJsonApiIndexParams(jsonFilters, page);

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiStudentIdCardRequests(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const idCardRequestColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'attributes.studentName',
                cell: ({ row }: { row: { original: IdCardRequest } }) => {
                    const request = row.original;
                    const label = request.attributes.studentName ?? '--';

                    if (!hasAbility(['view:student-id-card-requests', 'viewAny:student-id-card-requests'])) {
                        return label;
                    }

                    return textLink(route('admin.students.id-card-requests.show', request.id), label);
                },
            },
            {
                header: trans_choice('trans.student_number', 1),
                accessorKey: 'attributes.studentNumber',
                cell: ({ row }: { row: { original: IdCardRequest } }) => row.original.attributes.studentNumber ?? '--',
            },
            {
                header: trans('trans.student_id_card_reason'),
                accessorKey: 'attributes.reasonLabel',
                cell: ({ row }: { row: { original: IdCardRequest } }) => row.original.attributes.reasonLabel ?? '--',
            },
            {
                header: trans('trans.student_id_card_status'),
                accessorKey: 'attributes.status',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: IdCardRequest } }) => {
                    const { status, statusLabel } = row.original.attributes;

                    return tag(statusLabel ?? status, '', idCardRequestStatusTagVariant(status));
                },
            },
            {
                header: trans('trans.student_id_card_serial'),
                accessorKey: 'attributes.serialNumber',
                cell: ({ row }: { row: { original: IdCardRequest } }) => row.original.attributes.serialNumber ?? '—',
            },
            {
                header: trans('trans.created_at'),
                accessorKey: 'attributes.createdAt',
                cell: ({ row }: { row: { original: IdCardRequest } }) => {
                    const createdAt = row.original.attributes.createdAt;

                    return createdAt ? formatDate(createdAt, 'L') : '—';
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: IdCardRequest } }) => {
                    return actionButton({
                        title: trans('trans.view'),
                        variant: ColorVariant.success,
                        onClick: () => navigateTo(route('admin.students.id-card-requests.show', row.original.id)),
                    });
                },
            },
        ];
    };

    return {
        fetchIdCardRequests,
        idCardRequestColumns,
        isLoading,
    };
};
