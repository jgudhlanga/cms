<script setup lang="ts">
import type { StudentAccommodationFeesResponse } from '@/types/hms';
import { Wallet } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    fees: StudentAccommodationFeesResponse | null;
}

const props = defineProps<Props>();

const periodLabel = computed(
    () => props.fees?.intakeLabel ?? props.fees?.calendarYear ?? '—',
);

const statusBadge = computed(() =>
    props.fees?.isFullyPaid
        ? { textKey: 'students.accommodation_paid_badge', class: 'bg-primary text-primary-foreground' }
        : { textKey: 'students.accommodation_unpaid_badge', class: 'bg-muted text-muted-foreground' },
);
</script>

<template>
    <div v-if="!fees" class="rounded-xl border border-dashed border-border py-10 text-center text-sm text-muted-foreground">
        {{ $t('students.accommodation_fees_unavailable') }}
    </div>

    <div v-else class="rounded-xl border border-border bg-card p-4 shadow-sm sm:p-5">
        <div class="mt-4 grid grid-cols-3 gap-2">
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
        <div class="mt-5">
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
