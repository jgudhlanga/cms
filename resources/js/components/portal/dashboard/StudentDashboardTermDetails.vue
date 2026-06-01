<script setup lang="ts">
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

const nextPeriodHeadingKey = computed(() => {
    switch (props.calendarType) {
        case 'term':
            return 'students.dashboard_next_term_period';
        case 'abma':
            return 'students.dashboard_next_abma';
        default:
            return 'students.dashboard_next_semester';
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
</script>

<template>
    <div class="grid w-full min-w-0 grid-cols-1 gap-1.5 sm:grid-cols-2">
        <article class="min-w-0 rounded-md border border-primary/20 bg-card px-2 py-1.5 shadow-sm">
            <div class="flex items-center gap-1">
                <CalendarDays class="h-3 w-3 shrink-0 text-primary" />
                <h3 class="text-[9px] font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ $t(currentPeriodHeadingKey) }}
                </h3>
            </div>
            <template v-if="currentTerm">
                <p class="mt-0.5 wrap-break-word text-xs font-semibold leading-snug text-foreground sm:leading-tight">
                    {{ currentTerm.label }}
                    <span class="font-normal text-muted-foreground">· {{ currentTerm.calendarYear }}</span>
                </p>
                <dl class="mt-1 grid grid-cols-2 gap-x-2 gap-y-0.5 text-[10px] leading-tight">
                    <div>
                        <dt class="text-muted-foreground">{{ $t('students.dashboard_term_opens') }}</dt>
                        <dd class="font-medium text-foreground">{{ formatDate(currentTerm.openingDate) }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">{{ $t('students.dashboard_term_closes') }}</dt>
                        <dd class="font-medium text-foreground">{{ formatDate(currentTerm.closingDate) }}</dd>
                    </div>
                </dl>
            </template>
            <p
                v-else
                class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
            >
                {{ $t('students.dashboard_term_unavailable') }}
            </p>
        </article>

        <article class="min-w-0 rounded-md border border-sky-500/20 bg-card px-2 py-1.5 shadow-sm">
            <div class="flex items-center gap-1">
                <CalendarDays class="h-3 w-3 shrink-0 text-sky-500 dark:text-sky-400" />
                <h3 class="text-[9px] font-semibold uppercase tracking-wide text-muted-foreground">
                    {{ $t(nextPeriodHeadingKey) }}
                </h3>
            </div>
            <template v-if="nextTerm">
                <p class="mt-0.5 wrap-break-word text-xs font-semibold leading-snug text-foreground sm:leading-tight">
                    {{ nextTerm.label }}
                    <span class="font-normal text-muted-foreground">· {{ nextTerm.calendarYear }}</span>
                </p>
                <dl class="mt-1 grid grid-cols-2 gap-x-2 gap-y-0.5 text-[10px] leading-tight">
                    <div>
                        <dt class="text-muted-foreground">{{ $t('students.dashboard_term_opens') }}</dt>
                        <dd class="font-medium text-foreground">{{ formatDate(nextTerm.openingDate) }}</dd>
                    </div>
                    <div v-if="nextTerm.closingDate">
                        <dt class="text-muted-foreground">{{ $t('students.dashboard_term_closes') }}</dt>
                        <dd class="font-medium text-foreground">{{ formatDate(nextTerm.closingDate) }}</dd>
                    </div>
                </dl>
            </template>
            <p
                v-else
                class="mt-0.5 text-[10px] leading-snug text-muted-foreground"
            >
                {{ $t('students.dashboard_next_term_unavailable') }}
            </p>
        </article>
    </div>
</template>
