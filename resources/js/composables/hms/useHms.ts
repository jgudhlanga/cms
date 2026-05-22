import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, forbiddenAlert } from '@/lib/alerts';
import { mergeQueryParamsIntoRequestPath } from '@/lib/merge-query-into-url';
import { IconName } from '@/lib/icons';
import HttpService from '@/services/http.service';
import { hasAbility } from '@/lib/permissions';
import Hostels from '@/pages/hms/components/tabs/Hostels.vue';
import Rooms from '@/pages/hms/components/tabs/Rooms.vue';
import Students from '@/pages/hms/components/tabs/Students.vue';
import { CustomTab } from '@/types/utils';
import type {
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

    const fetchHostels = async (filters: HostelFiltersState = {}) => {
         try {
            isLoading.value = true;
            return await HttpService.get(route('v1.hms.hostels'), { params: filters });
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const roomListMergeOptions = { booleanParamKeys: ['with_trashed'] };
    const studentListMergeOptions = { booleanParamKeys: ['with_trashed'] };

    const fetchRooms = async (filters: HostelRoomFiltersState = {}, paginatorUrl?: string) => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('v1.hms.hostels.rooms');
            const path = mergeQueryParamsIntoRequestPath(baseUrl, filters as Record<string, unknown>, roomListMergeOptions);
            return await HttpService.get(path);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const fetchHostelAllocations = async (filters: HostelStudentFiltersState = {}, paginatorUrl?: string) => {
        try {
            isLoading.value = true;
            const baseUrl = paginatorUrl ?? route('v1.hms.hostel-allocations');
            const path = mergeQueryParamsIntoRequestPath(
                baseUrl,
                filters as Record<string, unknown>,
                studentListMergeOptions,
            );
            return await HttpService.get(path);
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
            { header: trans_choice('hms.floor', 1), accessorKey: 'attributes.floorNumber', meta: { align: 'center' }, },
            { header: trans_choice('hms.type', 1), accessorKey: 'attributes.roomType',  meta: { align: 'center' },
            cell: ({ row }: { row: { original: HostelRoom } }) => {
                return tag(row.original.attributes.roomType, '', ColorVariant.success);
            },
         },
            { header: trans('hms.occupancy'), accessorKey: 'attributes.occupancy',  meta: { align: 'center' }, },
            {
                header: trans_choice('hms.status', 1),
                accessorKey: 'status',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelRoom } }) => {
                    return tag(row.original.attributes.status, '', ColorVariant.primary);
                },
            },
            /* {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: Role } }) => {
                    const id = getIdParams(row.original.id?.toString() ?? '');
                    return moreActionButton(!!row.original?.attributes?.deletedAt, [
                        { key: 'edit', action: () => onOpenModal(hasAbility('update:roles'), row.original) },
                        { key: 'view', action: () => onView(hasAbility('view:roles'), route('roles.show', id)) },
                        {
                            key: 'archive',
                            action: () => onDelete(hasAbility('delete:roles'), route('roles.destroy', id), getName()),
                        },
                        {
                            key: 'restore',
                            action: () => onRestore(hasAbility('restore:roles'), route('roles.restore', id), getName()),
                        },
                        {
                            key: 'delete',
                            action: () => onForceDelete(hasAbility('forceDelete:roles'), route('roles.force-delete', id), getName()),
                        },
                    ]);
                },
            }, */
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
