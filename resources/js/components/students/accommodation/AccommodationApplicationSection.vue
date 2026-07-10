<script setup lang="ts">
import AccommodationApplicationForm from '@/components/students/accommodation/AccommodationApplicationForm.vue';
import BaseAlert from '@/components/core/alert/BaseAlert.vue';
import { IconButton } from '@/components/core/button';
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { useUtils } from '@/composables/core/useUtils';
import { TypeVariant } from '@/enums/type-variants';
import type {
    HostelAllocation,
    HostelApplication,
    HostelApplicationStudentLookupResponse,
    StudentAccommodationFeesResponse,
} from '@/types/hms';
import type { InertiaForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface FormShape {
    nextOfKinName: string;
    nextOfKinContact: string;
    checkIn: string;
    checkOut: string;
}

interface Props {
    applications: HostelApplication[];
    activeAllocation: HostelAllocation | null;
    openApplication: HostelApplication | null;
    lookup: HostelApplicationStudentLookupResponse | null;
    fees: StudentAccommodationFeesResponse | null;
    canApply: boolean;
    applyBlockers: string[];
    form: InertiaForm<FormShape>;
    isSaving: boolean;
    saveValidationError?: string | null;
    context: 'admin' | 'portal';
}

const props = defineProps<Props>();

const { formatDate } = useUtils();

const formatApplicationDateRange = (
    checkIn?: string | null,
    checkOut?: string | null,
): string => {
    const formattedCheckIn = checkIn ? formatDate(checkIn, 'L') : '—';
    const formattedCheckOut = checkOut ? formatDate(checkOut, 'L') : '—';

    return `${formattedCheckIn} — ${formattedCheckOut}`;
};

const emit = defineEmits<{
    submit: [];
}>();

const sortedApplications = computed(() =>
    [...props.applications].sort(
        (a, b) =>
            new Date(b.attributes.createdAt ?? 0).getTime()
            - new Date(a.attributes.createdAt ?? 0).getTime(),
    ),
);

const showForm = computed(
    () =>
        !props.activeAllocation
        && !props.openApplication
        && props.lookup?.found !== false,
);

const showNotFoundMessage = computed(() => props.lookup?.found === false);

const isAwaitingPayment = computed(
    () =>
        props.openApplication?.attributes.status === 'awaiting-payment'
        || props.openApplication?.attributes.status === 'partially-paid',
);

const showPaymentCta = computed(
    () =>
        props.context === 'portal'
        && isAwaitingPayment.value
        && !props.fees?.isFullyPaid,
);

const showFeesPaidConfirmation = computed(
    () =>
        props.context === 'portal'
        && props.fees?.isFullyPaid
        && ['awaiting-payment', 'partially-paid', 'paid'].includes(
            props.openApplication?.attributes.status ?? '',
        ),
);

const openApplicationStatusLabel = computed(() => {
    if (!props.openApplication) {
        return '';
    }

    const status = props.openApplication.attributes.status;

    if (
        props.fees?.isFullyPaid
        && (status === 'awaiting-payment' || status === 'partially-paid')
    ) {
        return trans('hms.application_status_paid');
    }

    return props.openApplication.attributes.statusLabel
        ?? props.openApplication.attributes.status
        ?? '';
});

const adminApplicationLink = (id: string | number) =>
    route('hostels.applications.show', { hostel_application: id });

const goToPayment = () => {
    router.visit(route('portal.profile.accommodations.pay'));
};

const reloadPage = () => {
    window.location.reload();
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <BaseAlert
            v-if="activeAllocation"
            :description="$t('students.accommodation_has_allocation')"
            :type="TypeVariant.info"
        />

        <div
            v-else-if="openApplication"
            class="rounded-lg border border-border bg-muted/20 p-3"
        >
            <div class="flex items-start justify-between gap-2">
                <p class="min-w-0 flex-1 text-sm text-foreground">
                    {{ $t('students.accommodation_open_application', {
                        status: openApplicationStatusLabel,
                    }) }}
                </p>
                <IconButton
                    v-if="context === 'portal'"
                    :icon="IconName.refresh"
                    tone="header-primary"
                    :aria-label="$t('trans.refresh')"
                    class="shrink-0"
                    @click="reloadPage"
                />
            </div>
            <p
                v-if="showFeesPaidConfirmation"
                class="mt-2 text-sm text-emerald-600 dark:text-emerald-400"
            >
                {{ $t('students.accommodation_payment_received_awaiting_review') }}
            </p>
            <div v-else-if="showPaymentCta" class="mt-3 flex flex-col gap-2">
                <p class="text-xs text-amber-600 dark:text-amber-400">
                    {{ $t('students.accommodation_payment_required') }}
                </p>
                <div>
                    <BaseButton
                        type="button"
                        :color="ColorVariant.primary"
                        :size="ButtonSize.md"
                        :title="$t('students.accommodation_proceed_to_payment')"
                        @click="goToPayment"
                    />
                </div>
            </div>
        </div>

        <BaseAlert
            v-else-if="showNotFoundMessage"
            :description="lookup?.message ?? $t('hms.student_not_found')"
            :type="TypeVariant.warning"
        />

        <template v-else-if="applyBlockers.length">
            <BaseAlert
                v-for="(blocker, index) in applyBlockers"
                :key="index"
                :description="blocker"
                :type="TypeVariant.warning"
            />
        </template>

        <AccommodationApplicationForm
            v-if="showForm"
            :form="form"
            :eligibility="lookup?.eligibility"
            :semester-label="lookup?.semester?.label"
            :check-in="lookup?.semester?.checkIn"
            :check-out="lookup?.semester?.checkOut"
            :can-submit="canApply"
            :is-saving="isSaving"
            :save-validation-error="saveValidationError"
            @submit="emit('submit')"
        />

        <div v-if="sortedApplications.length" class="rounded-xl border border-border bg-card p-4">
            <h4 class="mb-3 text-sm font-semibold text-foreground">{{ $t('students.accommodation_application_history') }}</h4>
            <ul class="flex flex-col gap-2">
                <li
                    v-for="application in sortedApplications"
                    :key="application.id"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-muted/30 px-3 py-2 text-sm"
                >
                    <div>
                        <p class="font-medium text-foreground">
                            {{ application.attributes.statusLabel ?? application.attributes.status }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatApplicationDateRange(
                                application.attributes.checkIn,
                                application.attributes.checkOut,
                            ) }}
                        </p>
                    </div>
                    <a
                        v-if="context === 'admin'"
                        :href="adminApplicationLink(application.id)"
                        class="text-xs font-medium text-primary hover:underline"
                    >
                        {{ $t('students.accommodation_view_application') }}
                    </a>
                </li>
            </ul>
        </div>

        <p
            v-else-if="!showForm && !activeAllocation && !openApplication && !showNotFoundMessage"
            class="text-sm text-muted-foreground"
        >
            {{ $t('students.accommodation_no_applications') }}
        </p>
    </div>
</template>
