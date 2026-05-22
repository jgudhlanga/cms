import { useUtils } from '@/composables/core/useUtils';
import { errorAlert } from '@/lib/alerts';
import {
    buildJsonApiIndexParams,
    jsonApiRequestConfig,
    mergeJsonApiFiltersIntoRequestPath,
    parseJsonApiHostelAllocations,
    parseJsonApiHostelRooms,
    parseJsonApiHostels,
    toHostelAllocationJsonApiFilters,
    toHostelJsonApiFilters,
    toHostelRoomJsonApiFilters,
} from '@/lib/json-api';
import { IconName } from '@/lib/icons';
import HttpService from '@/services/http.service';
import { hasAbility } from '@/lib/permissions';
import Hostels from '@/pages/hms/components/tabs/Hostels.vue';
import Rooms from '@/pages/hms/components/tabs/Rooms.vue';
import Students from '@/pages/hms/components/tabs/Students.vue';
import { CustomTab } from '@/types/utils';
import type { DataListProps } from '@/types/data-pagination';
import type {
    Hostel,
    HostelAllocation,
    HostelFiltersState,
    HostelRoom,
    HostelRoomFiltersState,
    HostelRoomStats,
    HostelStudentFiltersState,
} from '@/types/hms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { useDataTables } from '../core/useDataTables';
import { ColorVariant } from '@/enums/colors';

export const useHms = () => {
    const { formatDate } = useUtils();
    const isLoading = ref(false);
    const isStatsLoading = ref(false);
    const { moreActionButton, onView, tag, textLink } = useDataTables();

    const hmsTabs = (): Array<CustomTab> => {
        return [
            {
                transLabel: () => trans_choice('hms.hostel', 2),
                value: 'hostels',
                component: h(Hostels),
                show: true,
                icon: IconName.hostel,
            },
            {
                transLabel: () => trans_choice('hms.room', 2),
                value: 'rooms',
                component: h(Rooms),
                show: true,
                icon: IconName.room,
            },
            {
                transLabel: () => trans_choice('trans.student', 2),
                value: 'students',
                component: h(Students),
                show: true,
                icon: IconName.users,
            },
        ];
    };

    const fetchHostels = async (
        filters: HostelFiltersState = {},
        paginatorUrl?: string,
    ): Promise<DataListProps<Hostel> | undefined> => {
        try {
            isLoading.value = true;
            const jsonFilters = toHostelJsonApiFilters(filters);
            const path = paginatorUrl
                ? mergeJsonApiFiltersIntoRequestPath(paginatorUrl, jsonFilters)
                : route('v1.json.hostels.index');

            const params = paginatorUrl
                ? undefined
                : buildJsonApiIndexParams(jsonFilters);

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiHostels(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const fetchRooms = async (
        filters: HostelRoomFiltersState = {},
        paginatorUrl?: string,
    ): Promise<DataListProps<HostelRoom> | undefined> => {
        try {
            isLoading.value = true;
            const jsonFilters = toHostelRoomJsonApiFilters(filters);
            const path = paginatorUrl
                ? mergeJsonApiFiltersIntoRequestPath(paginatorUrl, jsonFilters)
                : route('v1.json.hostel-rooms.index');

            const params = paginatorUrl
                ? undefined
                : buildJsonApiIndexParams(jsonFilters);

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiHostelRooms(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const fetchHostelAllocations = async (
        filters: HostelStudentFiltersState = {},
        paginatorUrl?: string,
    ): Promise<DataListProps<HostelAllocation> | undefined> => {
        try {
            isLoading.value = true;
            const jsonFilters = toHostelAllocationJsonApiFilters(filters);
            const path = paginatorUrl
                ? mergeJsonApiFiltersIntoRequestPath(paginatorUrl, jsonFilters)
                : route('v1.json.hostel-room-allocations.index');

            const params = paginatorUrl
                ? undefined
                : buildJsonApiIndexParams(jsonFilters);

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiHostelAllocations(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const fetchRoomStats = async (): Promise<HostelRoomStats | undefined> => {
        try {
            isStatsLoading.value = true;
            const data = await HttpService.get(route('v1.hms.hostels.rooms.stats'));

            return {
                totalRooms: data.total_rooms ?? 0,
                totalCapacity: data.total_capacity ?? 0,
                totalMaxOccupancy: data.total_max_occupancy ?? 0,
                vacantCount: data.vacant_count ?? 0,
            };
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isStatsLoading.value = false;
        }
    };

    const hostelStudentColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'attributes.studentName',
                cell: ({ row }: { row: { original: HostelAllocation } }) => {
                    const studentId = row.original.attributes.studentId;
                    if (!studentId) {
                        return row.original.attributes.studentName ?? '--';
                    }
                    return textLink(
                        route('students.show', String(studentId)),
                        row.original.attributes.studentName ?? '--',
                    );
                },
            },
            {
                header: trans_choice('trans.student_number', 1),
                accessorKey: 'attributes.studentNumber',
                cell: ({ row }: { row: { original: HostelAllocation } }) => row.original.attributes.studentNumber ?? '--',
            },
            { header: trans_choice('trans.gender', 1), accessorKey: 'attributes.gender' },
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'attributes.course',
                cell: ({ row }: { row: { original: HostelAllocation } }) => row.original.attributes.course ?? '--',
            },
            {
                header: trans_choice('hms.hostel', 1),
                accessorKey: 'attributes.hostelName',
                cell: ({ row }: { row: { original: HostelAllocation } }) => row.original.attributes.hostelName ?? '--',
            },
            {
                header: trans_choice('hms.room', 1),
                accessorKey: 'attributes.roomName',
                cell: ({ row }: { row: { original: HostelAllocation } }) => row.original.attributes.roomName ?? '--',
            },
            {
                header: trans_choice('hms.type', 1),
                accessorKey: 'attributes.allocationType',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelAllocation } }) =>
                    tag(row.original.attributes.allocationTypeLabel ?? row.original.attributes.allocationType, '', ColorVariant.success),
            },
            {
                header: trans_choice('hms.status', 1),
                accessorKey: 'attributes.status',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelAllocation } }) =>
                    tag(row.original.attributes.statusLabel ?? row.original.attributes.status, '', ColorVariant.primary),
            },
            {
                header: trans('hms.check_in'),
                accessorKey: 'attributes.checkIn',
                cell: ({ row }: { row: { original: HostelAllocation } }) => {
                    const checkIn = row.original.attributes.checkIn;
                    return checkIn ? formatDate(checkIn, 'L') : '---';
                },
            },
            {
                header: trans('hms.check_out'),
                accessorKey: 'attributes.checkOut',
                cell: ({ row }: { row: { original: HostelAllocation } }) => {
                    const checkOut = row.original.attributes.checkOut;
                    return checkOut ? formatDate(checkOut, 'L') : '---';
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: HostelAllocation } }) => {
                    const studentId = row.original.attributes.studentId;
                    return moreActionButton(false, [
                        {
                            key: 'view',
                            action: () =>
                                onView(hasAbility('view:students'), route('students.show', String(studentId ?? ''))),
                        },
                    ]);
                },
            },
        ];
    };

    const hostelRoomColumns = () => {
        return [
            { header: trans_choice('hms.room', 1), accessorKey: 'attributes.name' },
            { header: trans_choice('hms.hostel', 1), accessorKey: 'attributes.hostelName' },
            { header: trans_choice('hms.floor', 1), accessorKey: 'attributes.floorNumber', meta: { align: 'center' } },
            {
                header: trans_choice('hms.type', 1),
                accessorKey: 'attributes.roomType',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelRoom } }) => {
                    return tag(row.original.attributes.roomType, '', ColorVariant.success);
                },
            },
            { header: trans('hms.occupancy'), accessorKey: 'attributes.occupancy', meta: { align: 'center' } },
            {
                header: trans_choice('hms.status', 1),
                accessorKey: 'status',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelRoom } }) => {
                    return tag(row.original.attributes.status, '', ColorVariant.primary);
                },
            },
        ];
    };

    return {
        hmsTabs,
        fetchHostels,
        fetchRooms,
        fetchHostelAllocations,
        fetchRoomStats,
        hostelRoomColumns,
        hostelStudentColumns,
        isLoading,
        isStatsLoading,
    };
};
