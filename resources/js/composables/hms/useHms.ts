import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, forbiddenAlert } from '@/lib/alerts';
import { IconName } from '@/lib/icons';
import HttpService from '@/services/http.service';
import Hostels from '@/pages/hms/components/tabs/Hostels.vue';
import Rooms from '@/pages/hms/components/tabs/Rooms.vue';
import { CustomTab } from '@/types/utils';
import type { HostelFiltersState, HostelRoom, HostelRoomFiltersState } from '@/types/hms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { useDataTables } from '../core/useDataTables';
import { hasAbility } from '@/lib/permissions';
import { router } from '@inertiajs/vue3';
import { getIdParams } from '@/lib/utils';
import { ColorVariant } from '@/enums/colors';

export const useHms = () => {
    const { isItTrue } = useUtils();
    const isLoading = ref(false);
    const { moreActionButton, countActionButton, onDelete, onRestore, onForceDelete, onView, tag } = useDataTables();
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

    const fetchRooms = async (filters: HostelRoomFiltersState = {}) => {
        try {
            isLoading.value = true;
            return await HttpService.get(route('v1.hms.hostels.rooms'), { params: filters });
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
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
        hostelRoomColumns,
    };
};
