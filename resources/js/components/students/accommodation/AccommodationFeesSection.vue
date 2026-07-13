<script setup lang="ts">
import type { StudentAccommodationFeesResponse } from '@/types/hms';
import { computed } from 'vue';

interface Props {
    fees: StudentAccommodationFeesResponse | null;
    context?: 'admin' | 'portal';
}

const props = withDefaults(defineProps<Props>(), {
    context: 'admin',
});

const periodLabel = computed(
    () => props.fees?.intakeLabel ?? props.fees?.calendarYear ?? '—',
);

const statusBadge = computed(() =>
    props.fees?.isFullyPaid
        ? { textKey: 'students.accommodation_paid_badge', class: 'bg-primary text-primary-foreground' }
        : { textKey: 'students.accommodation_unpaid_badge', class: 'bg-muted text-muted-foreground' },
);

const isPortal = computed(() => props.context === 'portal');
</script>

<template>
    <div v-if="!fees" class="rounded-xl border border-dashed border-border py-10 text-center text-sm text-muted-foreground">
        {{ $t('students.accommodation_fees_unavailable') }}
    </div>

    <div v-else :class="isPortal ? 'flex flex-col gap-4' : 'rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5'">
        <div v-if="isPortal" class="flex flex-wrap items-center gap-2">
            <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadge.class"
            >
                {{ $t(statusBadge.textKey) }}
            </span>
            <span class="text-sm text-muted-foreground">{{ periodLabel }}</span>
            <span class="text-sm text-muted-foreground">·</span>
            <span class="text-sm text-muted-foreground">{{ $t('students.accommodation_section_fees_desc') }}</span>
        </div>

        <div v-else class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <p class="text-xs text-muted-foreground">{{ periodLabel }}</p>
            </div>
            <span
                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="statusBadge.class"
            >
                {{ $t(statusBadge.textKey) }}
            </span>
        </div>

        <div class="grid grid-cols-3 gap-2">
            <div class="rounded-lg bg-muted/40 p-3 text-center">
                <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_fee_total') }}</p>
                <p class="font-semibold text-foreground">{{ fees.total }}</p>
            </div>
            <div class="rounded-lg bg-muted/40 p-3 text-center">
                <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_fee_paid_amount') }}</p>
                <p class="font-semibold text-emerald-600 dark:text-emerald-400">{{ fees.paid }}</p>
            </div>
            <div class="rounded-lg bg-muted/40 p-3 text-center">
                <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_fee_due_amount') }}</p>
                <p class="font-semibold text-foreground">{{ fees.due }}</p>
            </div>
        </div>

        <div>
            <h5 class="mb-2 text-sm font-semibold text-foreground">{{ $t('students.accommodation_payment_history') }}</h5>
            <ul v-if="fees.paymentHistory?.length" class="flex flex-col gap-2">
                <li
                    v-for="(payment, index) in fees.paymentHistory"
                    :key="index"
                    class="flex items-center justify-between gap-2 text-sm"
                >
                    <span class="text-foreground">
                        {{ payment.amount }}
                        <span v-if="payment.description" class="text-muted-foreground">
                            — {{ payment.description }}
                        </span>
                    </span>
                    <span class="shrink-0 text-muted-foreground">{{ payment.date }}</span>
                </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">{{ $t('students.accommodation_no_payments') }}</p>
        </div>
    </div>
</template>
