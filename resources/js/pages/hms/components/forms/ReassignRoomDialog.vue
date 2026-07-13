<script setup lang="ts">
import InputError from '@/components/core/form/InputError.vue';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import BaseModal from '@/components/core/modal/BaseModal.vue';
import { Label } from '@/components/ui/label';
import { SizeVariant } from '@/enums/sizes';
import { closeModal, getModalEdit } from '@/lib/alerts';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { useModalStore } from '@/store/core/useModalStore';
import type {
    HostelAllocation,
    HostelApplicationApprovalHostelOption,
    HostelApplicationApprovalRoomOption,
} from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const {
    fetchAllocationReassignmentOptions,
    fetchAllocationReassignmentRooms,
    reassignHostelAllocation,
} = useHms();
const { modals } = useModalStore();
const hmsStore = useHmsStore();

const allocation = ref<HostelAllocation | null>(null);
const hostels = ref<HostelApplicationApprovalHostelOption[]>([]);
const rooms = ref<HostelApplicationApprovalRoomOption[]>([]);
const isLoadingHostels = ref(false);
const isLoadingRooms = ref(false);
const roomsLoaded = ref(false);
const selectedHostel = ref<SelectOption | string | number | null>(null);
const selectedRoom = ref<SelectOption | string | number | null>(null);

const form = useForm({
    hostelRoomId: null as number | null,
});

const resolveSelectId = (value: SelectOption | string | number | null | undefined): number | null => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const raw = typeof value === 'object' && value !== null && 'value' in value ? value.value : value;
    const id = Number(raw);

    return Number.isFinite(id) && id > 0 ? id : null;
};

const hasSelectedHostel = computed(() => resolveSelectId(selectedHostel.value) !== null);

const studentLabel = computed(() => allocation.value?.attributes.studentName ?? '');

const hostelSelectOptions = computed<SelectOption[]>(() =>
    hostels.value.map((hostel) => ({
        value: hostel.id,
        label: hostel.isFull
            ? `${hostel.name} (${trans('hms.hostel_full')})`
            : `${hostel.name} (${hostel.availableBeds} ${trans('hms.available_beds')})`,
        disabled: hostel.isFull,
    })),
);

const roomSelectOptions = computed<SelectOption[]>(() =>
    rooms.value.map((room) => ({
        value: room.id,
        label: `${room.name} (${room.occupancyLabel})`,
    })),
);

const showActionButton = computed(
    () => form.hostelRoomId !== null && hostelSelectOptions.value.some((option) => !option.disabled),
);

const loadHostels = async (): Promise<void> => {
    if (!allocation.value) {
        return;
    }

    isLoadingHostels.value = true;
    try {
        hostels.value = await fetchAllocationReassignmentOptions(allocation.value);
    } finally {
        isLoadingHostels.value = false;
    }
};

const loadRooms = async (hostelId: number): Promise<void> => {
    if (!allocation.value) {
        return;
    }

    isLoadingRooms.value = true;
    roomsLoaded.value = false;
    try {
        rooms.value = await fetchAllocationReassignmentRooms(allocation.value, hostelId);
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
        rooms.value = [];
        roomsLoaded.value = false;
        return;
    }

    await loadRooms(hostelId);
};

watch(modals!, () => {
    allocation.value = getModalEdit(APP_MODULE_KEYS.hostel_room_reassign) ?? null;
    hostels.value = [];
    rooms.value = [];
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();
    roomsLoaded.value = false;

    if (allocation.value) {
        void loadHostels();
    }
});

watch(selectedRoom, (room) => {
    form.hostelRoomId = resolveSelectId(room);
    clearFormErrors(form, 'hostelRoomId');
});

const onClose = (): void => {
    allocation.value = null;
    hostels.value = [];
    rooms.value = [];
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();
    roomsLoaded.value = false;
};

const save = async (): Promise<void> => {
    if (!allocation.value || !form.hostelRoomId) {
        form.setError('hostelRoomId', trans('hms.hostel_room_required_for_approval'));
        return;
    }

    const ok = await reassignHostelAllocation(allocation.value, form.hostelRoomId);

    if (ok) {
        hmsStore.refreshStudents();
        hmsStore.refreshRooms();
        closeModal(APP_MODULE_KEYS.hostel_room_reassign);
        onClose();
    }
};
</script>

<template>
    <BaseModal
        :name="APP_MODULE_KEYS.hostel_room_reassign"
        :title="$t('hms.reassign_room')"
        :size="SizeVariant.sm"
        :action-btn-text="'hms.reassign_room'"
        :cancel-btn-text="'trans.cancel'"
        :show-action-button="showActionButton"
        :on-form-action="() => save()"
        :on-close-modal="onClose"
        :form="form"
    >
        <template #body>
            <div class="space-y-4">
                <p v-if="studentLabel" class="text-sm text-muted-foreground">
                    {{ studentLabel }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ $t('hms.reassign_room_description') }}
                </p>

                <div class="space-y-2">
                    <Label>
                        {{ $t('hms.select_hostel') }}
                        <RequiredIndicator />
                    </Label>
                    <BaseSelect
                        v-model="selectedHostel"
                        :options="hostelSelectOptions"
                        :placeholder="$t('hms.select_hostel')"
                        :is-required="true"
                        :is-clearable="false"
                        :loading="isLoadingHostels"
                        @update:model-value="onHostelChange"
                    />
                </div>

                <div v-if="hasSelectedHostel" class="space-y-2">
                    <Label>
                        {{ $t('hms.select_room') }}
                        <RequiredIndicator />
                    </Label>
                    <BaseSelect
                        v-model="selectedRoom"
                        :options="roomSelectOptions"
                        :placeholder="$t('hms.select_room')"
                        :is-required="true"
                        :is-clearable="false"
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
            </div>
        </template>
    </BaseModal>
</template>
