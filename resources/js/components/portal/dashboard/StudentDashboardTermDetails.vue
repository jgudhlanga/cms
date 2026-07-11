<script setup lang="ts">
import { useStudentPortalTermProgress } from '@/composables/students/useStudentPortalTermProgress';
import type { StudentPortalCalendarType, StudentPortalDashboardTerm } from '@/types/students';
import { CalendarDays } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    calendarType?: StudentPortalCalendarType;
    currentTerm: StudentPortalDashboardTerm | null;
    nextTerm: StudentPortalDashboardTerm | null;
}

const props = withDefaults(defineProps<Props>(), {
    calendarType: 'semester',
});

const { daysRemaining, elapsedPercent, isTodayWithinTerm } = useStudentPortalTermProgress(
    () => props.currentTerm,
);

const currentPeriodHeadingKey = computed(() => {
    switch (props.calendarType) {
        case 'term':
            return 'students.dashboard_current_term_period';
        case 'abma':
            return 'students.dashboard_current_abma';
        default:
            return 'students.dashboard_current_semester';
    }
});

const periodNounKey = computed(() => {
    switch (props.calendarType) {
        case 'term':
            return 'students.dashboard_period_term';
        case 'abma':
            return 'students.dashboard_period_abma';
        default:
            return 'students.dashboard_period_semester';
    }
});

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(`${value}T00:00:00`));
};

const termTitle = (term: StudentPortalDashboardTerm): string => {
    return `${term.label} · ${term.calendarYear}`;
};
</script>

<template>
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card px-4 py-4 shadow-sm sm:px-6 sm:py-5">
        <template v-if="currentTerm">
            <div class="flex min-w-0 flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between sm:gap-3">
                <p class="min-w-0 wrap-break-word">
                    <span class="text-[11px] font-semibold uppercase tracking-wide text-primary">
                        {{ $t(currentPeriodHeadingKey) }}
                    </span>
                    <span class="ml-2 text-base font-bold text-foreground">
                        {{ termTitle(currentTerm) }}
                    </span>
                </p>
                <p
                    v-if="daysRemaining !== null"
                    class="shrink-0 text-sm text-muted-foreground"
                >
                    <span class="font-semibold text-foreground">{{ daysRemaining }}</span>
                    {{ $t('students.dashboard_days_remaining', { period: $t(periodNounKey) }) }}
                </p>
            </div>

            <div class="mt-3">
                <div class="relative h-2 rounded-full bg-muted">
                    <div
                        class="absolute inset-y-0 left-0 rounded-full bg-primary transition-all"
                        :style="{ width: `${elapsedPercent}%` }"
                    />
                    <div
                        class="absolute top-1/2 size-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-primary bg-background shadow-sm"
                        :style="{ left: `${elapsedPercent}%` }"
                    />
                </div>
            </div>

            <div class="mt-2 flex items-center justify-between gap-2 text-xs">
                <p class="min-w-0 wrap-break-word text-muted-foreground">
                    {{ $t('students.dashboard_term_opens') }} ·
                    <span class="font-medium text-foreground">{{ formatDate(currentTerm.openingDate) }}</span>
                </p>
                <p class="flex min-w-0 items-center justify-end gap-1.5 wrap-break-word text-muted-foreground">
                    {{ $t('students.dashboard_term_closes') }}
                    <span
                        v-if="isTodayWithinTerm"
                        class="rounded bg-primary/15 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary"
                    >
                        {{ $t('students.dashboard_today') }}
                    </span>
                    <span class="font-medium text-foreground">{{ formatDate(currentTerm.closingDate) }}</span>
                </p>
            </div>
        </template>

        <p
            v-else
            class="text-sm text-muted-foreground"
        >
            {{ $t('students.dashboard_term_unavailable') }}
        </p>

        <div class="my-4 border-t border-dashed border-border" />

        <div class="flex min-w-0 items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-600 dark:text-amber-400">
                <CalendarDays class="size-4" />
            </span>
            <div class="flex min-w-0 flex-1 flex-col gap-0.5 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
                        {{ $t('students.dashboard_next_up') }}
                    </p>
                    <p
                        v-if="nextTerm"
                        class="wrap-break-word text-sm font-semibold text-foreground"
                    >
                        {{ termTitle(nextTerm) }}
                    </p>
                </div>
                <p
                    v-if="nextTerm"
                    class="shrink-0 text-xs text-muted-foreground"
                >
                    {{ $t('students.dashboard_term_opens') }} {{ formatDate(nextTerm.openingDate) }}
                    <template v-if="nextTerm.closingDate">
                        → {{ $t('students.dashboard_term_closes') }} {{ formatDate(nextTerm.closingDate) }}
                    </template>
                </p>
                <p
                    v-else
                    class="text-xs text-muted-foreground"
                >
                    {{ $t('students.dashboard_next_term_unavailable') }}
                </p>
            </div>
        </div>
    </section>
</template>
