import { hostelApplicationBlockerMessage } from '@/lib/hms/applicationBlockers';
import {
    buildJsonApiIndexParams,
    jsonApiRequestConfig,
    parseJsonApiHostelAllocations,
    parseJsonApiHostelApplications,
} from '@/lib/json-api';
import HttpService from '@/services/http.service';
import type {
    HostelAllocation,
    HostelAllocationRoommate,
    HostelApplication,
    HostelApplicationStudentLookupResponse,
    StudentAccommodationFeesResponse,
} from '@/types/hms';
import { errorAlert } from '@/lib/alerts';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

type AccommodationContext = 'admin' | 'portal';

export function useStudentAccommodations(
    studentId: () => string,
    studentNumber: () => string | undefined,
    context: () => AccommodationContext,
) {
    const isLoading = ref(false);
    const loadError = ref<string | null>(null);
    const allocations = ref<HostelAllocation[]>([]);
    const applications = ref<HostelApplication[]>([]);
    const lookup = ref<HostelApplicationStudentLookupResponse | null>(null);
    const fees = ref<StudentAccommodationFeesResponse | null>(null);
    const roommates = ref<HostelAllocationRoommate[]>([]);

    const activeAllocation = computed(() =>
        allocations.value.find((row) => row.attributes.status === 'active') ?? allocations.value[0] ?? null,
    );

    const openApplication = computed(() =>
        applications.value.find((row) =>
            ['pending', 'awaiting-payment'].includes(row.attributes.status ?? ''),
        ) ?? null,
    );

    const canApply = computed(() => {
        if (activeAllocation.value || openApplication.value) {
            return false;
        }

        const blockers = lookup.value?.applyBlockers ?? lookup.value?.blockers ?? [];

        if (blockers.length > 0) {
            return false;
        }

        if (lookup.value?.found === false) {
            return false;
        }

        return lookup.value?.canApply ?? lookup.value?.canSubmit ?? lookup.value?.found === true;
    });

    const applyBlockers = computed(() =>
        (lookup.value?.applyBlockers ?? lookup.value?.blockers ?? []).map((key) =>
            hostelApplicationBlockerMessage(key),
        ),
    );

    const fetchAllocations = async (id: string) => {
        const document = await HttpService.get(route('v1.json.hms.hostel-room-allocations.index'), {
            ...jsonApiRequestConfig(),
            params: buildJsonApiIndexParams({ student: id }, { size: 20 }),
        });
        allocations.value = parseJsonApiHostelAllocations(document).data;
    };

    const fetchApplications = async (id: string) => {
        const document = await HttpService.get(route('v1.json.hms.hostel-applications.index'), {
            ...jsonApiRequestConfig(),
            params: buildJsonApiIndexParams({ student: id }, { size: 20 }),
        });
        applications.value = parseJsonApiHostelApplications(document).data;
    };

    const fetchFees = async (id: string) => {
        const response = await HttpService.get(
            route('v1.json.hostel-applications.accommodationFees', { filter: { student: id } }),
            jsonApiRequestConfig(),
        );
        fees.value = (response?.meta ?? response) as StudentAccommodationFeesResponse;
    };

    const fetchLookup = async () => {
        if (context() === 'portal') {
            const response = await HttpService.get(
                route('v1.json.hostel-applications.selfLookup'),
                jsonApiRequestConfig(),
            );
            lookup.value = (response?.meta ?? response) as HostelApplicationStudentLookupResponse;
            return;
        }

        const id = studentId();
        const response = await HttpService.get(
            route('v1.json.hostel-applications.studentLookup', { filter: { student: id } }),
            jsonApiRequestConfig(),
        );
        lookup.value = (response?.meta ?? response) as HostelApplicationStudentLookupResponse;
    };

    const fetchRoommates = async (allocationId: string | number) => {
        const response = await HttpService.get(
            route('v1.json.hostel-room-allocations.roommates', allocationId),
            jsonApiRequestConfig(),
        );
        const meta = response?.meta ?? response;
        roommates.value = meta?.roommates ?? [];
    };

    const load = async () => {
        const id = studentId();

        if (!id) {
            return;
        }

        isLoading.value = true;
        loadError.value = null;

        try {
            await Promise.all([fetchAllocations(id), fetchApplications(id), fetchFees(id), fetchLookup()]);

            if (activeAllocation.value) {
                await fetchRoommates(activeAllocation.value.id);
            } else {
                roommates.value = [];
            }
        } catch {
            loadError.value = trans('students.accommodations_load_failure');
            errorAlert(trans('students.accommodations_load_failure'));
        } finally {
            isLoading.value = false;
        }
    };

    return {
        isLoading,
        loadError,
        allocations,
        applications,
        activeAllocation,
        openApplication,
        lookup,
        fees,
        roommates,
        canApply,
        applyBlockers,
        load,
        refresh: load,
    };
}
