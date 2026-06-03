<script setup lang="ts">
import type { HostelAllocation, HostelApplication, StudentAccommodationFeesResponse } from '@/types/hms';
import { Bed, Receipt, FileText } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    activeAllocation: HostelAllocation | null;
    openApplication: HostelApplication | null;
    fees: StudentAccommodationFeesResponse | null;
    openQueriesCount?: number;
}

const props = defineProps<Props>();

const roomSummary = computed(() => {
    if (!props.activeAllocation) {
        return null;
    }

    const attrs = props.activeAllocation.attributes;
    const room = attrs.roomName ?? '—';
    const hostel = attrs.hostelName ?? '';

    return hostel ? `${room} — ${hostel}` : room;
});

const feeSummary = computed(() => {
    if (!props.fees) {
        return null;
    }

    if (props.fees.isFullyPaid) {
        return { labelKey: 'students.accommodation_fee_paid', variant: 'success' as const };
    }

    if (parseFloat(props.fees.due) > 0) {
        return {
            labelKey: 'students.accommodation_fee_due',
            variant: 'warning' as const,
            amount: props.fees.due,
        };
    }

    return { labelKey: 'students.accommodation_fee_view', variant: 'muted' as const };
});

const applicationSummary = computed(() => {
    if (!props.openApplication) {
        return null;
    }

    return props.openApplication.attributes.statusLabel
        ?? props.openApplication.attributes.status
        ?? '';
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                        <Bed class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_my_room') }}</p>
                        <p class="truncate font-semibold text-foreground">
                            {{ roomSummary ?? $t('students.accommodation_no_room') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600 text-white dark:bg-emerald-700">
                        <Receipt class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-muted-foreground">{{ $t('students.accommodation_fee_status') }}</p>
                        <p
                            v-if="feeSummary"
                            class="font-semibold text-foreground"
                        >
                            <template v-if="feeSummary.variant === 'success'">
                                {{ $t(feeSummary.labelKey) }}
                            </template>
                            <template v-else-if="feeSummary.variant === 'warning'">
                                {{ $t(feeSummary.labelKey, { amount: feeSummary.amount }) }}
                            </template>
                            <template v-else>
                                {{ $t(feeSummary.labelKey) }}
                            </template>
                        </p>
                        <p v-else class="text-sm text-muted-foreground">—</p>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="(openQueriesCount ?? 0) > 0"
            class="rounded-xl border border-border bg-card p-4 shadow-sm"
        >
            <div class="flex items-center gap-2">
                <FileText class="h-4 w-4 text-primary" />
                <p class="text-sm font-medium text-foreground">{{ $t('students.accommodation_queries_heading') }}</p>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ $t('students.accommodation_open_queries', { count: openQueriesCount }) }}
            </p>
        </div>

        <div
            v-if="applicationSummary"
            class="rounded-xl border border-border bg-card p-4 shadow-sm"
        >
            <div class="flex items-center gap-2">
                <FileText class="h-4 w-4 text-primary" /> 
                <p class="text-sm font-medium text-foreground">{{ $t('students.accommodation_application_status') }}</p>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">{{ applicationSummary }}</p>
        </div>
    </div>
</template>
