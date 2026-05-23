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
import { APP_MODULE_KEYS } from '@/lib/constants';
import { clearFormErrors } from '@/lib/forms';
import { useHms } from '@/composables/hms/useHms';
import { useHmsStore } from '@/store/hms/useHmsStore';
import { useModalStore } from '@/store/core/useModalStore';
import type {
    HostelApplication,
    HostelApplicationApprovalHostelOption,
    HostelApplicationApprovalOptionsResponse,
    HostelApplicationApprovalRoomOption,
} from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

const { fetchApplicationApprovalOptions, approveApplication } = useHms();
const { modals } = useModalStore();
const hmsStore = useHmsStore();

const application = ref<HostelApplication | null>(null);
const options = ref<HostelApplicationApprovalOptionsResponse | null>(null);
const isLoadingOptions = ref(false);
const selectedHostel = ref<SelectOption | null>(null);
const selectedRoom = ref<SelectOption | null>(null);

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
        not_pending: trans('hms.application_cannot_be_approved'),
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

const showActionButton = computed(
    () => (options.value?.canApprove ?? false) && hostelSelectOptions.value.some((option) => !option.disabled),
);

const loadOptions = async (hostelId?: number | null): Promise<void> => {
    if (!application.value) {
        return;
    }

    isLoadingOptions.value = true;
    try {
        const response = await fetchApplicationApprovalOptions(application.value, hostelId);
        if (!response) {
            return;
        }

        if (hostelId) {
            options.value = {
                ...(options.value ?? response),
                rooms: response.rooms,
            };
        } else {
            options.value = response;
            selectedHostel.value = null;
            selectedRoom.value = null;
            form.hostelRoomId = null;
        }
    } finally {
        isLoadingOptions.value = false;
    }
};

watch(modals!, () => {
    application.value = getModalEdit(APP_MODULE_KEYS.hostel_application_approve) ?? null;
    options.value = null;
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();

    if (application.value) {
        void loadOptions();
    }
});

watch(selectedHostel, async (hostel) => {
    selectedRoom.value = null;
    form.hostelRoomId = null;
    form.clearErrors('hostelRoomId');

    if (!hostel?.value) {
        if (options.value) {
            options.value = { ...options.value, rooms: [] };
        }
        return;
    }

    await loadOptions(Number(hostel.value));
});

watch(selectedRoom, (room) => {
    form.hostelRoomId = room?.value ? Number(room.value) : null;
    clearFormErrors(form, 'hostelRoomId');
});

const onClose = (): void => {
    application.value = null;
    options.value = null;
    selectedHostel.value = null;
    selectedRoom.value = null;
    form.reset();
    form.clearErrors();
};

const save = async (): Promise<void> => {
    if (!application.value) {
        return;
    }

    if (!form.hostelRoomId) {
        form.setError('hostelRoomId', trans('hms.hostel_room_required_for_approval'));
        return;
    }

    const ok = await approveApplication(application.value, form.hostelRoomId);
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

                <template v-if="!isLoadingOptions && (options?.hostels?.length ?? 0) > 0">
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
                        />
                    </div>

                    <div v-if="selectedHostel" class="space-y-2">
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
                            :disabled="roomSelectOptions.length === 0"
                        />
                        <p v-if="roomSelectOptions.length === 0" class="text-sm text-muted-foreground">
                            {{ $t('hms.hostel_full') }}
                        </p>
                        <InputError :message="form.errors.hostelRoomId" />
                    </div>
                </template>

                <p v-else-if="isLoadingOptions" class="text-sm text-muted-foreground">
                    {{ $t('trans.loading') }}…
                </p>
            </div>
        </template>
    </BaseModal>
</template>
