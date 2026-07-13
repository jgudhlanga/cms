<script setup lang="ts">
import { IconButton } from '@/components/core/button';
import BaseButton from '@/components/core/button/BaseButton.vue';
import { ButtonSize } from '@/enums/buttons';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { useUtils } from '@/composables/core/useUtils';
import { hostelApplicationProgressSteps } from '@/lib/hms/hostelApplicationProgress';
import type { HostelApplication, StudentAccommodationFeesResponse } from '@/types/hms';
import { router } from '@inertiajs/vue3';
import { trans } from 'laravel-vue-i18n';
import { computed } from 'vue';

interface Props {
    openApplication: HostelApplication;
    fees: StudentAccommodationFeesResponse | null;
    context: 'admin' | 'portal';
    showProgress?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showProgress: true,
});

const { formatDate } = useUtils();

const status = computed(() => props.openApplication.attributes.status);

const isAwaitingPayment = computed(
    () => status.value === 'awaiting-payment' || status.value === 'partially-paid',
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
        && ['awaiting-payment', 'partially-paid', 'paid'].includes(status.value ?? ''),
);

const openApplicationStatusLabel = computed(() => {
    if (
        props.fees?.isFullyPaid
        && (status.value === 'awaiting-payment' || status.value === 'partially-paid')
    ) {
        return trans('hms.application_status_paid');
    }

    return props.openApplication.attributes.statusLabel
        ?? props.openApplication.attributes.status
        ?? '';
});

const dateRange = computed(() => {
    const checkIn = props.openApplication.attributes.checkIn;
    const checkOut = props.openApplication.attributes.checkOut;
    const formattedCheckIn = checkIn ? formatDate(checkIn, 'L') : '—';

    if (props.context === 'portal') {
        return formattedCheckIn;
    }

    const formattedCheckOut = checkOut ? formatDate(checkOut, 'L') : '—';

    return `${formattedCheckIn} — ${formattedCheckOut}`;
});

const progressSteps = computed(() =>
    hostelApplicationProgressSteps(status.value, props.fees?.isFullyPaid ?? false),
);

const goToPayment = () => {
    router.visit(route('portal.profile.accommodations.pay'));
};

const reloadPage = () => {
    window.location.reload();
};

function stepCircleClass(stepState: string): string {
    if (stepState === 'completed') {
        return 'border-emerald-600 bg-emerald-600 text-white dark:border-emerald-700 dark:bg-emerald-700';
    }

    if (stepState === 'active') {
        return 'border-primary bg-primary text-primary-foreground';
    }

    return 'border-border bg-muted text-muted-foreground';
}

function stepConnectorClass(stepState: string): string {
    return stepState === 'completed' ? 'bg-emerald-600 dark:bg-emerald-700' : 'bg-border';
}
</script>

<template>
    <div class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-sm text-foreground">
                    {{ $t('students.accommodation_open_application', {
                        status: openApplicationStatusLabel,
                    }) }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ dateRange }}
                </p>
            </div>
            <IconButton
                v-if="context === 'portal'"
                :icon="IconName.refresh"
                tone="header-primary"
                :aria-label="$t('trans.refresh')"
                class="shrink-0"
                @click="reloadPage"
            />
        </div>

        <div v-if="showProgress" class="mt-4">
            <ol class="flex items-start gap-0">
                <li
                    v-for="(step, index) in progressSteps"
                    :key="step.key"
                    class="flex min-w-0 flex-1 flex-col items-center"
                >
                    <div class="flex w-full items-center">
                        <div
                            v-if="index > 0"
                            class="h-0.5 flex-1"
                            :class="stepConnectorClass(progressSteps[index - 1].state)"
                        />
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 text-xs font-semibold"
                            :class="stepCircleClass(step.state)"
                        >
                            {{ index + 1 }}
                        </span>
                        <div
                            v-if="index < progressSteps.length - 1"
                            class="h-0.5 flex-1"
                            :class="stepConnectorClass(step.state)"
                        />
                    </div>
                    <p
                        class="mt-2 px-1 text-center text-[10px] leading-tight sm:text-xs"
                        :class="step.state === 'pending' ? 'text-muted-foreground' : 'font-medium text-foreground'"
                    >
                        {{ $t(step.labelKey) }}
                    </p>
                </li>
            </ol>
        </div>

        <p
            v-if="showFeesPaidConfirmation"
            class="mt-4 text-sm text-emerald-600 dark:text-emerald-400"
        >
            {{ $t('students.accommodation_payment_received_awaiting_review') }}
        </p>
        <div v-else-if="showPaymentCta" class="mt-4 flex flex-col gap-2">
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
</template>
