import { useUtils } from '@/composables/core/useUtils';
import { errorAlert, successAlert, openModal } from '@/lib/alerts';
import {
    buildJsonApiIndexParams,
    jsonApiRequestConfig,
    jsonApiWriteConfig,
    mergeJsonApiFiltersIntoRequestPath,
    parseJsonApiHostelAmenities,
    parseJsonApiHostelAllocations,
    parseJsonApiHostelApplication,
    parseJsonApiHostelRooms,
    parseJsonApiHostelRoomStats,
    parseJsonApiHostels,
    parseJsonApiHmsSettings,
    toHostelAllocationJsonApiFilters,
    toHostelApplicationJsonApiFilters,
    toHostelJsonApiFilters,
    toHostelRoomJsonApiFilters,
    parseJsonApiHostelApplications,
} from '@/lib/json-api';
import { IconName } from '@/lib/icons';
import { buildHostelRoomShowUrl, currentHostelPageReturnPath } from '@/lib/hms/hostelRoomNavigation';
import HttpService from '@/services/http.service';
import { hasAbility } from '@/lib/permissions';
import { buildStudentShowUrl } from '@/lib/studentShowNavigation';
import Hostels from '@/pages/hms/components/tabs/Hostels.vue';
import Amenities from '@/pages/hms/components/tabs/Amenities.vue';
import Rooms from '@/pages/hms/components/tabs/Rooms.vue';
import Students from '@/pages/hms/components/tabs/Students.vue';
import Applications from '@/pages/hms/components/tabs/Applications.vue';
import Settings from '@/pages/hms/components/tabs/Settings.vue';
import { CustomTab } from '@/types/utils';
import type { DataListProps } from '@/types/data-pagination';
import type {
    Hostel,
    HostelAmenity,
    HostelAllocation,
    HostelApplication,
    HostelApplicationFiltersState,
    HostelApplicationApprovalOptionsResponse,
    HostelApplicationApprovalRoomOption,
    HostelApplicationApprovalRoomsResponse,
    HostelApplicationPendingQueueResponse,
    HostelApplicationStudentLookupResponse,
    HostelFiltersState,
    HostelRoom,
    HostelRoomFiltersState,
    HostelRoomStats,
    HostelStudentFiltersState,
    HmsSettings,
} from '@/types/hms';
import { trans, trans_choice } from 'laravel-vue-i18n';
import { h, ref } from 'vue';
import { useDataTables } from '../core/useDataTables';
import { ColorVariant } from '@/enums/colors';
import {
    hostelApplicationStatusTagVariant,
    hostelApplicationTypeTagVariant,
} from '@/lib/hms/applicationTagVariants';
import { APP_MODULE_KEYS } from '@/lib/constants';

export const useHms = () => {
    const { formatDate, navigateTo } = useUtils();
    const isLoading = ref(false);
    const isStatsLoading = ref(false);
    const { moreActionButton, onDelete, onRestore, onView, tag, textLink, actionButton } = useDataTables();

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
            {
                transLabel: () => trans_choice('hms.application', 2),
                value: 'applications',
                component: h(Applications),
                show: hasAbility('viewAny:hostel-applications'),
                icon: IconName.file,
            },
            {
                transLabel: () => trans_choice('hms.amenity', 2),
                value: 'amenities',
                component: h(Amenities),
                show: hasAbility(['viewAny:hostel-amenities', 'view:hostel-amenities']),
                icon: IconName.settings,
            },
            {
                transLabel: () => trans_choice('hms.settings', 2),
                value: 'settings',
                component: h(Settings),
                show: hasAbility(['view:hms-settings', 'update:hms-settings', 'crud-settings:hms-settings']),
                icon: IconName.settings,
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
                : route('v1.json.hms.hostels.index');

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
                : route('v1.json.hms.hostel-rooms.index');

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

    const fetchAmenities = async (
        paginatorUrl?: string,
    ): Promise<DataListProps<HostelAmenity> | undefined> => {
        try {
            isLoading.value = true;
            const path = paginatorUrl
                ? paginatorUrl
                : route('v1.json.hms.hostel-amenities.index');

            const params = paginatorUrl ? undefined : buildJsonApiIndexParams({});

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiHostelAmenities(document);
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
                : route('v1.json.hms.hostel-room-allocations.index');

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
            const document = await HttpService.get(route('v1.json.hostel-rooms.stats'), jsonApiRequestConfig());

            return parseJsonApiHostelRoomStats(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isStatsLoading.value = false;
        }
    };

    const fetchApplications = async (
        filters: HostelApplicationFiltersState = {},
        paginatorUrl?: string,
        page?: { number?: number; size?: number },
        silent = false,
    ): Promise<DataListProps<HostelApplication> | undefined> => {
        try {
            if (!silent) {
                isLoading.value = true;
            }
            const jsonFilters = toHostelApplicationJsonApiFilters(filters);
            const path = paginatorUrl
                ? mergeJsonApiFiltersIntoRequestPath(paginatorUrl, jsonFilters)
                : route('v1.json.hms.hostel-applications.index');

            const params = paginatorUrl ? undefined : buildJsonApiIndexParams(jsonFilters, page);

            const document = await HttpService.get(path, {
                ...jsonApiRequestConfig(),
                ...(params ? { params } : {}),
            });

            return parseJsonApiHostelApplications(document);
        } catch {
            if (!silent) {
                errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
            }
        } finally {
            if (!silent) {
                isLoading.value = false;
            }
        }
    };

    const fetchApplication = async (id: string | number): Promise<HostelApplication | undefined> => {
        try {
            isLoading.value = true;
            const document = await HttpService.get(route('v1.json.hms.hostel-applications.show', id), jsonApiRequestConfig());

            return parseJsonApiHostelApplication(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        } finally {
            isLoading.value = false;
        }
    };

    const fetchApplicationStudentLookup = async (search: string): Promise<HostelApplicationStudentLookupResponse | undefined> => {
        try {
            const document = await HttpService.get(route('v1.json.hostel-applications.studentLookup'), {
                ...jsonApiRequestConfig(),
                params: { filter: { search } },
            });

            return document.meta as HostelApplicationStudentLookupResponse;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        }
    };

    const saveApplication = async (attributes: Record<string, unknown>, id?: string | number): Promise<boolean> => {
        try {
            const payload = {
                data: {
                    type: 'hostel-applications',
                    ...(id ? { id: String(id) } : {}),
                    attributes,
                },
            };

            if (id) {
                await HttpService.patch(route('v1.json.hms.hostel-applications.update', id), payload, jsonApiWriteConfig());
            } else {
                await HttpService.post(route('v1.json.hms.hostel-applications.store'), payload, jsonApiWriteConfig());
            }

            successAlert(trans('hms.application_saved'));
            return true;
        } catch {
            errorAlert(trans('hms.application_save_failed'));
            return false;
        }
    };

    const updateApplicationStatus = async (
        application: HostelApplication,
        status: 'approved' | 'awaiting-payment' | 'declined',
        declineReason?: string,
    ): Promise<boolean> => {
        return saveApplication(
            {
                status,
                ...(declineReason ? { declineReason } : {}),
            },
            application.id,
        );
    };

    const hostelApplicationRouteParam = (applicationId: string | number) => ({
        hostel_application: String(applicationId),
    });

    const fetchApplicationApprovalOptions = async (
        application: HostelApplication,
    ): Promise<HostelApplicationApprovalOptionsResponse | undefined> => {
        try {
            const document = await HttpService.get(
                route('v1.json.hostel-applications.approvalOptions', hostelApplicationRouteParam(application.id)),
                jsonApiRequestConfig(),
            );

            return document.meta as HostelApplicationApprovalOptionsResponse;
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        }
    };

    const fetchApplicationApprovalRooms = async (
        application: HostelApplication,
        hostelId: number,
    ): Promise<HostelApplicationApprovalRoomOption[]> => {
        try {
            const document = await HttpService.get(
                route('v1.json.hostel-applications.approvalRooms', hostelApplicationRouteParam(application.id)),
                {
                    ...jsonApiRequestConfig(),
                    params: { hostelId },
                },
            );

            const meta = document.meta as HostelApplicationApprovalRoomsResponse;

            return meta?.rooms ?? [];
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
            return [];
        }
    };

    const fetchHostelRoomsForApplication = async (
        applicationId: string | number,
        hostelId: number,
    ): Promise<HostelApplicationApprovalRoomOption[]> => {
        try {
            const document = await HttpService.get(route('v1.json.hms.hostel-rooms.index'), {
                ...jsonApiRequestConfig(),
                params: buildJsonApiIndexParams(
                    toHostelRoomJsonApiFilters({
                        hostel: hostelId,
                        availableForApplication: applicationId,
                    }),
                    { size: 100 },
                ),
            });

            const parsed = parseJsonApiHostelRooms(document);

            return (parsed?.data ?? []).map((room) => ({
                id: Number(room.id),
                name: room.attributes.name,
                maxOccupancy: room.attributes.maxOccupancy,
                currentOccupancy: 0,
                availableBeds: 0,
                occupancyLabel: room.attributes.occupancy ?? '',
            }));
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
            return [];
        }
    };

    const fetchPendingApplicationQueue = async (
        excludeApplicationId?: string | number | null,
    ): Promise<HostelApplicationPendingQueueResponse['applications']> => {
        try {
            const document = await HttpService.get(route('v1.json.hostel-applications.pendingQueue'), {
                ...jsonApiRequestConfig(),
                ...(excludeApplicationId
                    ? { params: { exclude: String(excludeApplicationId) } }
                    : {}),
            });

            const meta = document.meta as HostelApplicationPendingQueueResponse;

            return meta?.applications ?? [];
        } catch {
            return [];
        }
    };

    const approveApplication = async (
        application: HostelApplication,
        hostelRoomId?: number | null,
    ): Promise<boolean> => {
        return saveApplication(
            {
                status: 'approved',
                ...(hostelRoomId ? { hostelRoomId } : {}),
            },
            application.id,
        );
    };

    const fetchHmsSettings = async (): Promise<HmsSettings | undefined> => {
        try {
            const document = await HttpService.get(route('v1.json.hms.hms-settings.index'), jsonApiRequestConfig());
            return parseJsonApiHmsSettings(document);
        } catch {
            errorAlert(trans('trans.load_data_failure', { data: trans('trans.data') }));
        }
    };

    const saveHmsSettings = async (settings: HmsSettings, attributes: Partial<HmsSettings['attributes']>): Promise<boolean> => {
        try {
            await HttpService.patch(
                route('v1.json.hms.hms-settings.update', settings.id),
                {
                    data: {
                        type: 'hms-settings',
                        id: String(settings.id),
                        attributes,
                    },
                },
                jsonApiWriteConfig(),
            );

            successAlert(trans('hms.settings_saved'));
            return true;
        } catch {
            errorAlert(trans('hms.settings_save_failed'));
            return false;
        }
    };

    const hostelApplicationColumns = () => {
        return [
            {
                header: trans_choice('trans.name', 1),
                accessorKey: 'attributes.displayName',
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const application = row.original;
                    const label = application.attributes.displayName ?? '--';

                    if (!hasAbility('view:hostel-applications')) {
                        return label;
                    }

                    return textLink(route('hostels.applications.show', application.id), label);
                },
            },
            {
                header: trans_choice('trans.student_number', 1),
                accessorKey: 'attributes.studentNumber',
                cell: ({ row }: { row: { original: HostelApplication } }) => row.original.attributes.studentNumber ?? '--',
            },
            {
                header: trans_choice('trans.gender', 1),
                accessorKey: 'attributes.gender',
                cell: ({ row }: { row: { original: HostelApplication } }) => row.original.attributes.gender ?? '--',
            },
            {
                header: trans_choice('trans.course', 1),
                accessorKey: 'attributes.course',
                cell: ({ row }: { row: { original: HostelApplication } }) => row.original.attributes.course ?? '--',
            },
            {
                header: trans_choice('hms.type', 1),
                accessorKey: 'attributes.applicationType',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const { applicationType, applicationTypeLabel } = row.original.attributes;

                    return tag(
                        applicationTypeLabel ?? applicationType,
                        '',
                        hostelApplicationTypeTagVariant(applicationType),
                    );
                },
            },
            {
                header: trans('hms.check_in'),
                accessorKey: 'attributes.checkIn',
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const checkIn = row.original.attributes.checkIn;
                    return checkIn ? formatDate(checkIn, 'L') : '---';
                },
            },
            {
                header: trans('hms.check_out'),
                accessorKey: 'attributes.checkOut',
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const checkOut = row.original.attributes.checkOut;
                    return checkOut ? formatDate(checkOut, 'L') : '---';
                },
            },
            {
                header: trans_choice('hms.status', 1),
                accessorKey: 'attributes.status',
                meta: { align: 'center' },
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const { status, statusLabel } = row.original.attributes;

                    return tag(
                        statusLabel ?? status,
                        '',
                        hostelApplicationStatusTagVariant(status),
                    );
                },
            },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: HostelApplication } }) => {
                    const application = row.original;
                    const actions = [];

                    if (hasAbility('view:hostel-applications')) {
                        actions.push({
                            key: 'view',
                            action: () => onView(true, route('hostels.applications.show', application.id)),
                        });
                    }

                    return actionButton({
                        title: trans('trans.view'),
                        variant: ColorVariant.success,
                        onClick: () => navigateTo(route('hostels.applications.show', application.id)),
                    });
                },
            },
        ];
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
                        buildStudentShowUrl(studentId, { from: 'hms' }),
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
                                onView(hasAbility('view:students'), buildStudentShowUrl(String(studentId ?? ''), { from: 'hms' })),
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
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: HostelRoom } }) => {
                    const room = row.original;

                    return moreActionButton(!!room.attributes.deletedAt, [
                        {
                            key: 'view',
                            action: () => onView(
                                hasAbility(['viewAny:hostel-rooms', 'view:hostel-rooms']),
                                buildHostelRoomShowUrl(room.id, {
                                    return: currentHostelPageReturnPath(
                                        typeof window !== 'undefined'
                                            ? `${window.location.pathname}${window.location.search}`
                                            : route('hostels.index'),
                                    ),
                                }),
                            ),
                        },
                        {
                            key: 'edit',
                            action: () => {
                                if (!hasAbility('update:hostel-rooms')) {
                                    return;
                                }

                                openModal({ name: APP_MODULE_KEYS.hostel_rooms, edit: room });
                            },
                        },
                    ]);
                },
            },
        ];
    };

    const hostelAmenityColumns = () => {
        return [
            { header: trans_choice('hms.amenity', 1), accessorKey: 'attributes.name' },
            { header: trans('trans.slug'), accessorKey: 'attributes.slug' },
            {
                header: trans_choice('trans.action', 2),
                accessorKey: 'actions',
                enableSorting: false,
                meta: { align: 'right' },
                cell: ({ row }: { row: { original: HostelAmenity } }) => {
                    const amenity = row.original;

                    return moreActionButton(!!amenity.attributes.deletedAt, [
                        {
                            key: 'edit',
                            action: () => {
                                if (!hasAbility('update:hostel-amenities')) {
                                    return;
                                }

                                openModal({ name: APP_MODULE_KEYS.hostel_amenities, edit: amenity });
                            },
                        },
                        {
                            key: 'delete',
                            action: () => {
                                onDelete(
                                    hasAbility('delete:hostel-amenities'),
                                    route('hostel-amenities.destroy', String(amenity.id)),
                                    amenity.attributes.name,
                                );
                            },
                        },
                        {
                            key: 'restore',
                            action: () => {
                                onRestore(
                                    hasAbility('restore:hostel-amenities'),
                                    route('hostel-amenities.restore', String(amenity.id)),
                                    amenity.attributes.name,
                                );
                            },
                        },
                    ]);
                },
            },
        ];
    };

    return {
        hmsTabs,
        fetchHostels,
        fetchAmenities,
        fetchRooms,
        fetchHostelAllocations,
        fetchRoomStats,
        fetchApplications,
        fetchApplication,
        fetchApplicationStudentLookup,
        fetchApplicationApprovalOptions,
        fetchApplicationApprovalRooms,
        fetchHostelRoomsForApplication,
        fetchPendingApplicationQueue,
        saveApplication,
        updateApplicationStatus,
        approveApplication,
        fetchHmsSettings,
        saveHmsSettings,
        hostelAmenityColumns,
        hostelRoomColumns,
        hostelStudentColumns,
        hostelApplicationColumns,
        isLoading,
        isStatsLoading,
    };
};
