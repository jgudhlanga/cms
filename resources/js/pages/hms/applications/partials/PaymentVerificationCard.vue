<script setup lang="ts">
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import BaseCard from '@/components/core/card/BaseCard.vue';
import BaseButton from '@/components/core/button/BaseButton.vue';
import InputError from '@/components/core/form/InputError.vue';
import { ColorVariant } from '@/enums/colors';
import RequiredIndicator from '@/components/core/form/RequiredIndicator.vue';
import BaseRadioGroup from '@/components/core/form/radio-group/BaseRadioGroup.vue';
import BaseSelect from '@/components/core/form/select/BaseSelect.vue';
import { Label } from '@/components/ui/label';
import AllocationPreviewCard from '@/pages/hms/applications/partials/AllocationPreviewCard.vue';
import { useCustomConfirmDialog } from '@/composables/core/useCustomConfirmDialog';
import { useUtils } from '@/composables/core/useUtils';
import { useHms } from '@/composables/hms/useHms';
import { TypeVariant } from '@/enums/type-variants';
import { errorAlert } from '@/lib/alerts';
import { clearFormErrors } from '@/lib/forms';
import type {
    HostelApplication,
    HostelApplicationAllocationPreview,
    HostelApplicationApprovalHostelOption,
    HostelApplicationApprovalOptionsResponse,
    HostelApplicationApprovalRoomOption,
    HostelApplicationPaymentVerification,
    HostelApplicationPaymentVerificationKey,
} from '@/types/hms';
import type { SelectOption } from '@/types/utils';
import { useForm } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

interface Props {
    application: HostelApplication;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    approved: [];
    decline: [];
}>();

const { fetchApplicationApprovalOptions, fetchApplicationApprovalRooms, fetchApplicationAllocationPreview, fetchHostelRoomsForApplication, saveApplication } = useHms();
const { open: openConfirm } = useCustomConfirmDialog();
const { isItTrue } = useUtils();

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

const CONFIRMED_YES = 'true';
const CONFIRMED_NO = 'false';

const form = useForm({
    addressOutsideCityCampusConfirmed: null as string | null,
    fullTimeStudentConfirmed: null as string | null,
    tuitionFeesPaidConfirmed: null as string | null,
    accommodationFeesPaidConfirmed: null as string | null,
    hostelRoomId: null as number | null,
});

const verificationFields: {
    key: HostelApplicationPaymentVerificationKey;
    labelKey: string;
    prefix: string;
}[] = [
    { key: 'addressOutsideCityCampusConfirmed', labelKey: 'hms.label_confirm_address_outside_city', prefix: 'address' },
    { key: 'fullTimeStudentConfirmed', labelKey: 'hms.label_confirm_full_time_student', prefix: 'full_time' },
    { key: 'tuitionFeesPaidConfirmed', labelKey: 'hms.label_confirm_tuition_fees_paid', prefix: 'tuition' },
    { key: 'accommodationFeesPaidConfirmed', labelKey: 'hms.label_confirm_accommodation_fees_paid', prefix: 'accommodation' },
];

const activeVerificationFields = computed(() => {
    const required = options.value?.requiredPaymentVerification ?? [];

    return verificationFields.filter((field) => required.includes(field.key));
});

const radioOptionsForPrefix = (prefix: string) =>
    yesNoOptions.value.map((option) => ({
        ...option,
        inputId: `${prefix}_${option.inputId}`,
    }));

const isAnswered = (value: string | boolean | null | undefined): boolean =>
    value !== null && value !== undefined && value !== '';

const isConfirmedYes = (value: string | boolean | null | undefined): boolean => isItTrue(value);

const toConfirmationValue = (value: boolean | null | undefined): string | null => {
    if (value === true) {
        return CONFIRMED_YES;
    }

    if (value === false) {
        return CONFIRMED_NO;
    }

    return null;
};

const yesNo = () => ({ yes: trans('trans.yes'), no: trans('trans.no') });

const yesNoOptions = computed(() => {
    const { yes, no } = yesNo();

    return [
        { inputId: 'confirmed_yes', label: yes, value: CONFIRMED_YES },
        { inputId: 'confirmed_no', label: no, value: CONFIRMED_NO },
    ];
});

const blockerMessage = (key: string): string => {
    const messages: Record<string, string> = {
        not_awaiting_payment: trans('hms.application_not_awaiting_payment'),
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

const isAutoAllocationEnabled = computed(() => options.value?.autoAllocateRooms ?? false);

const showRoomSelection = computed(
    () =>
        (options.value?.canApprove ?? false)
        && !isAutoAllocationEnabled.value
        && hostelSelectOptions.value.some((option) => !option.disabled),
);

const allVerificationsAnswered = computed(() => {
    const fields = activeVerificationFields.value;

    if (fields.length === 0) {
        return true;
    }

    return fields.every((field) => isAnswered(form[field.key]));
});

const canApproveAndAllocate = computed(
    () => {
        if (isAutoAllocationEnabled.value) {
            return (options.value?.canApprove ?? false) && allVerificationsAnswered.value;
        }

        return showRoomSelection.value && allVerificationsAnswered.value && form.hostelRoomId !== null;
    },
);

const isDirectAllocationOnly = computed(
    () => (options.value?.allowsDirectAllocation ?? false) && activeVerificationFields.value.length === 0,
);

const hydrateFromApplication = (verification?: HostelApplicationPaymentVerification | null): void => {
    form.addressOutsideCityCampusConfirmed = toConfirmationValue(verification?.addressOutsideCityCampusConfirmed);
    form.fullTimeStudentConfirmed = toConfirmationValue(verification?.fullTimeStudentConfirmed);
    form.tuitionFeesPaidConfirmed = toConfirmationValue(verification?.tuitionFeesPaidConfirmed);
    form.accommodationFeesPaidConfirmed = toConfirmationValue(verification?.accommodationFeesPaidConfirmed);
};

const loadAllocationPreview = async (hostelRoomId?: number | null): Promise<void> => {
    if (!(options.value?.canApprove ?? false)) {
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
            props.application,
            hostelRoomId ?? undefined,
        );
    } finally {
        isLoadingPreview.value = false;
    }
};

const loadHostels = async (): Promise<void> => {
    isLoadingHostels.value = true;
    try {
        const response = await fetchApplicationApprovalOptions(props.application);
        if (!response) {
            options.value = null;
            return;
        }

        options.value = {
            ...response,
            rooms: [],
        };
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
    isLoadingRooms.value = true;
    roomsLoaded.value = false;
    try {
        let rooms = await fetchApplicationApprovalRooms(props.application, hostelId);

        if (rooms.length === 0) {
            rooms = await fetchHostelRoomsForApplication(props.application.id, hostelId);
        }

        if (options.value) {
            options.value = {
                ...options.value,
                rooms,
            };
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

watch(
    () => props.application.id,
    () => {
        hydrateFromApplication(props.application.attributes.paymentVerification);
        selectedHostel.value = null;
        selectedRoom.value = null;
        form.hostelRoomId = null;
        options.value = null;
        allocationPreview.value = null;
        void loadHostels();
    },
    { immediate: true },
);

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

const paymentVerificationPayload = (): HostelApplicationPaymentVerification => {
    const payload: HostelApplicationPaymentVerification = {};

    for (const field of activeVerificationFields.value) {
        payload[field.key] = isConfirmedYes(form[field.key]);
    }

    return payload;
};

const validateConfirmations = (): boolean => {
    if (!allVerificationsAnswered.value) {
        errorAlert(trans('hms.error_payment_verification_required'));
        return false;
    }

    return true;
};

const approveAndAllocate = async (): Promise<void> => {
    if (!validateConfirmations()) {
        return;
    }

    if (!isAutoAllocationEnabled.value && !form.hostelRoomId) {
        form.setError('hostelRoomId', trans('hms.hostel_room_required_for_approval'));
        return;
    }

    const confirmed = await openConfirm({
        title: trans('hms.verify_allocate_dialog_title'),
        message: trans(
            isAutoAllocationEnabled.value
                ? 'hms.verify_auto_allocate_dialog_message'
                : 'hms.verify_allocate_dialog_message',
        ),
        note: '',
        confirmText: trans('hms.button_approve_and_allocate'),
    });

    if (!confirmed) {
        return;
    }

    const ok = await saveApplication(
        {
            status: 'approved',
            ...(isAutoAllocationEnabled.value ? {} : { hostelRoomId: form.hostelRoomId }),
            paymentVerification: paymentVerificationPayload(),
        },
        props.application.id,
    );

    if (ok) {
        emit('approved');
    }
};
</script>

<template>
    <BaseCard
        :title="isDirectAllocationOnly ? $t('hms.room_allocation_card_title') : $t('hms.payment_verification_card_title')"
        :description="isDirectAllocationOnly ? $t('hms.room_allocation_card_description') : $t('hms.payment_verification_card_description')"
    >
        <p v-if="activeVerificationFields.length" class="text-sm text-muted-foreground">
            {{ $t('hms.payment_verification_yes_helper') }}
        </p>

        <div v-if="activeVerificationFields.length" class="grid grid-cols-2 gap-4">
            <div
                v-for="field in activeVerificationFields"
                :key="field.key"
                class="flex items-center space-x-5"
            >
                <Label class="font-bold">{{ $t(field.labelKey) }}</Label>
                <BaseRadioGroup
                    :options="radioOptionsForPrefix(field.prefix) as any"
                    v-model="form[field.key]"
                    :vertical-layout="false"
                />
            </div>
        </div>

        <BaseAlert
            v-for="blocker in options?.blockers ?? []"
            :key="blocker"
            class="mt-4"
            :title="blockerMessage(blocker)"
            :type="TypeVariant.danger"
        />

        <template v-if="showRoomSelection">
            <div class="mt-6 space-y-4">
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

        <p v-else-if="isAutoAllocationEnabled" class="mt-4 text-sm text-muted-foreground">
            {{ $t('hms.auto_allocate_rooms_helper') }}
        </p>

        <p v-if="isLoadingPreview" class="mt-4 text-sm text-muted-foreground">
            {{ $t('trans.loading') }}…
        </p>

        <AllocationPreviewCard
            v-else-if="allocationPreview"
            class="mt-4"
            :preview="allocationPreview"
        />

        <p
            v-else-if="(options?.canApprove ?? false) && (isAutoAllocationEnabled || form.hostelRoomId)"
            class="mt-4 text-sm text-muted-foreground"
        >
            {{ $t('hms.allocation_preview_unavailable') }}
        </p>

        <p v-else-if="isLoadingHostels" class="mt-4 text-sm text-muted-foreground">
            {{ $t('trans.loading') }}…
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
            <BaseButton
                type="button"
                :title="$t('hms.button_approve_and_allocate')"
                :disabled="!canApproveAndAllocate"
                @click="approveAndAllocate"
            />
            <BaseButton
                type="button"
                :variant="ColorVariant.danger"
                :title="$t('hms.decline_application')"
                @click="emit('decline')"
            />
        </div>
    </BaseCard>
</template>
