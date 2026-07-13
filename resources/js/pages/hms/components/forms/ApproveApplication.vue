<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Label } from '@/components/ui/label';
import { TypeVariant } from '@/enums/type-variants';
import { SizeVariant } from '@/enums/sizes';
import { closeModal, getModalEdit } from '@/lib/alerts';
import { hasAbility } from '@/lib/permissions';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import AllocationPreviewCard from '@/pages/hms/applications/partials/AllocationPreviewCard.vue';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { useModalStore } from '@/store/core/useModalStore';
import type {
    HostelApplication,
    HostelApplicationAllocationPreview,
    HostelApplicationApprovalHostelOption,
    HostelApplicationApprovalOptionsResponse,
    HostelApplicationApprovalRoomOption,
} from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const { fetchApplicationApprovalOptions, fetchApplicationApprovalRooms, fetchApplicationAllocationPreview, fetchHostelRoomsForApplication, approveApplication } = useHms();
const { modals } = useModalStore();
const hmsStore = useHmsStore();

const application = ref<HostelApplication | null>(null);
const options = ref<HostelApplicationApprovalOptionsResponse | null>(null);
const allocationPreview = ref<HostelApplicationAllocationPreview | null>(null);
const isLoadingHostels = ref(false);
const isLoadingRooms = ref(false);
const isLoadingPreview = ref(false);
const roomsLoaded = ref(false);
const selectedHostel = ref<SelectOption | string | number | null>(null);
const selectedRoom = ref<SelectOption | string | number | null>(null);

const resolveSelectId = (value: SelectOption | string | number | null | undefined): number | null => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const raw = typeof value === 'object' && value !== null && 'value' in value ? value.value : value;
    const id = Number(raw);

    return Number.isFinite(id) && id > 0 ? id : null;
};

const hasSelectedHostel = computed(() => resolveSelectId(selectedHostel.value) !== null);
const isAutoAllocationEnabled = computed(() => options.value?.autoAllocateRooms ?? false);

const form = useForm({
    hostelRoomId: null as number | null,
});

const applicantLabel = computed(() => {
    const attrs = application.value?.attributes;
    if (!attrs) {
        return '';
    }

    return attrs.displayName ?? attrs.studentName ?? attrs.name ?? attrs.studentNumber ?? '';
});

const blockerMessage = (key: string): string => {
    const messages: Record<string, string> = {
        not_awaiting_payment: trans('hms.application_not_awaiting_payment'),
        not_pending: trans('hms.application_not_awaiting_payment'),
        guest_not_allocatable: trans('hms.guest_approval_not_supported'),
        unknown_gender_for_hostel: trans('hms.unknown_gender_for_hostel'),
        no_hostel_capacity: trans('hms.no_hostel_capacity'),
        student_already_allocated: trans('hms.student_already_allocated'),
    };

    return messages[key] ?? key;
};

const hostelSelectOptions = computed<SelectOption[]>(() =>
    (options.value?.hostels ?? []).map((hostel: HostelApplicationApprovalHostelOption) => ({
        value: hostel.id,
        label: hostel.isFull
            ? `${hostel.name} (${trans('hms.hostel_full')})`
            : `${hostel.name} (${hostel.availableBeds} ${trans('hms.available_beds')})`,
        disabled: hostel.isFull,
    })),
);

const roomSelectOptions = computed<SelectOption[]>(() =>
    (options.value?.rooms ?? []).map((room: HostelApplicationApprovalRoomOption) => ({
        value: room.id,
        label: `${room.name} (${room.occupancyLabel})`,
    })),
);

const canOverrideRoom = computed(
    () => hasAbility('update:hostel-applications') || hasAbility('update:hostel-room-allocations'),
);

const showRoomSelection = computed(
    () =>
        (options.value?.canApprove ?? false)
        && hostelSelectOptions.value.some((option) => !option.disabled)
        && (!isAutoAllocationEnabled.value || canOverrideRoom.value),
);

const showActionButton = computed(
    () => {
        if (!(options.value?.canApprove ?? false)) {
            return false;
        }

        if (isAutoAllocationEnabled.value) {
            return form.hostelRoomId !== null || allocationPreview.value !== null;
        }

        return showRoomSelection.value && form.hostelRoomId !== null;
    },
);

const loadAllocationPreview = async (hostelRoomId?: number | null): Promise<void> => {
    if (!application.value || !(options.value?.canApprove ?? false)) {
        allocationPreview.value = null;
        return;
    }

    if (!isAutoAllocationEnabled.value && (hostelRoomId ?? 0) < 1) {
        allocationPreview.value = null;
        return;
    }

    isLoadingPreview.value = true;
    try {
        allocationPreview.value = await fetchApplicationAllocationPreview(
            application.value,
            hostelRoomId ?? undefined,
        );
    } finally {
        isLoadingPreview.value = false;
    }
};

const loadHostels = async (): Promise<void> => {
    if (!application.value) {
        return;
    }

    isLoadingHostels.value = true;
    try {
        const response = await fetchApplicationApprovalOptions(application.value);
        if (!response) {
            return;
        }

        options.value = { ...response, rooms: [] };
        selectedHostel.value = null;
        selectedRoom.value = null;
        form.hostelRoomId = null;
        roomsLoaded.value = false;
        allocationPreview.value = null;

        if (response.canApprove && (response.autoAllocateRooms ?? false)) {
            await loadAllocationPreview();
        }
    } finally {
        isLoadingHostels.value = false;
    }
};

const loadRooms = async (hostelId: number): Promise<void> => {
    if (!application.value) {
        return;
    }

    isLoadingRooms.value = true;
    roomsLoaded.value = false;
    try {
        let rooms = await fetchApplicationApprovalRooms(application.value, hostelId);

        if (rooms.length === 0) {
            rooms = await fetchHostelRoomsForApplication(application.value.id, hostelId);
        }

        if (options.value) {
            options.value = { ...options.value, rooms };
        }
    } finally {
        isLoadingRooms.value = false;
        roomsLoaded.value = true;
    }
};

const onHostelChange = async (value: SelectOption | string | number | null): Promise<void> => {
    selectedRoom.value = null;
    form.hostelRoomId = null;
    form.clearErrors('hostelRoomId');

    const hostelId = resolveSelectId(value);

    if (hostelId === null) {
        if (options.value) {
            options.value = { ...options.value, rooms: [] };
        }
        roomsLoaded.value = false;
        allocationPreview.value = null;
        return;
    }

    await loadRooms(hostelId);
};

watch(modals!, () => {
    application.value = getModalEdit(APP_MODULE_KEYS.hostel_application_approve) ?? null;
    options.value = null;
    allocationPreview.value = null;
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();
    roomsLoaded.value = false;

    if (application.value) {
        void loadHostels();
    }
});

watch(selectedRoom, (room) => {
    form.hostelRoomId = resolveSelectId(room);
    clearFormErrors(form, 'hostelRoomId');

    const roomId = resolveSelectId(room);
    if (roomId !== null) {
        void loadAllocationPreview(roomId);
    } else {
        allocationPreview.value = null;
    }
});

const onClose = (): void => {
    application.value = null;
    options.value = null;
    allocationPreview.value = null;
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();
    roomsLoaded.value = false;
};

const save = async (): Promise<void> => {
    if (!application.value) {
        return;
    }

    if (!isAutoAllocationEnabled.value && !form.hostelRoomId) {
        form.setError('hostelRoomId', trans('hms.hostel_room_required_for_approval'));
        return;
    }

    if (isAutoAllocationEnabled.value && !form.hostelRoomId && !allocationPreview.value) {
        form.setError('hostelRoomId', trans('hms.no_hostel_capacity'));
        return;
    }

    const ok = await approveApplication(
        application.value,
        form.hostelRoomId,
    );
    if (ok) {
        hmsStore.refreshApplications();
        hmsStore.refreshStudents();
        hmsStore.refreshRooms();
        closeModal(APP_MODULE_KEYS.hostel_application_approve);
        onClose();
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_application_approve"
        :title="$t('hms.approve_application')"
        :size="SizeVariant.sm"
        :action-btn-text="'hms.approve_application'"
        :cancel-btn-text="'trans.cancel'"
        :show-action-button="showActionButton"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="space-y-4">
                <p v-if="applicantLabel" class="text-sm text-muted-foreground">
                    {{ applicantLabel }}
                </p>

                <BaseAlert
                    v-for="blocker in options?.blockers ?? []"
                    :key="blocker"
                    :title="blockerMessage(blocker)"
                    :type="TypeVariant.danger"
                />

                <template v-if="showRoomSelection">
                    <p v-if="isAutoAllocationEnabled" class="text-sm text-muted-foreground">
                        {{ $t('hms.room_override_helper') }}
                    </p>

                    <div class="space-y-2">
                        <Label>
                            {{ $t('hms.select_hostel') }}
                            <RequiredIndicator v-if="!isAutoAllocationEnabled" />
                        </Label>
                        <BaseSelect
                            v-model="selectedHostel"
                            :options="hostelSelectOptions"
                            :placeholder="$t('hms.select_hostel')"
                            :is-required="!isAutoAllocationEnabled"
                            :is-clearable="isAutoAllocationEnabled"
                            :loading="isLoadingHostels"
                            @update:model-value="onHostelChange"
                        />
                    </div>

                    <div v-if="hasSelectedHostel" class="space-y-2">
                        <Label>
                            {{ $t('hms.select_room') }}
                            <RequiredIndicator v-if="!isAutoAllocationEnabled" />
                        </Label>
                        <BaseSelect
                            v-model="selectedRoom"
                            :options="roomSelectOptions"
                            :placeholder="$t('hms.select_room')"
                            :is-required="!isAutoAllocationEnabled"
                            :is-clearable="isAutoAllocationEnabled"
                            :loading="isLoadingRooms"
                            :disabled="isLoadingRooms || roomSelectOptions.length === 0"
                        />
                        <p v-if="isLoadingRooms" class="text-sm text-muted-foreground">
                            {{ $t('trans.loading') }}…
                        </p>
                        <p v-else-if="roomsLoaded && roomSelectOptions.length === 0" class="text-sm text-muted-foreground">
                            {{ $t('hms.no_rooms_found') }}
                        </p>
                        <InputError :message="form.errors.hostelRoomId" />
                    </div>
                </template>

                <p v-else-if="isAutoAllocationEnabled" class="text-sm text-muted-foreground">
                    {{ $t('hms.auto_allocate_rooms_helper') }}
                </p>

                <p v-if="isLoadingPreview" class="text-sm text-muted-foreground">
                    {{ $t('trans.loading') }}…
                </p>

                <AllocationPreviewCard
                    v-else-if="allocationPreview"
                    :preview="allocationPreview"
                />

                <p
                    v-else-if="(options?.canApprove ?? false) && (isAutoAllocationEnabled || form.hostelRoomId)"
                    class="text-sm text-muted-foreground"
                >
                    {{ $t('hms.allocation_preview_unavailable') }}
                </p>

                <p v-else-if="isLoadingHostels" class="text-sm text-muted-foreground">
                    {{ $t('trans.loading') }}…
                </p>
            </div>
        </template>
    </BaseModal>
</template>
