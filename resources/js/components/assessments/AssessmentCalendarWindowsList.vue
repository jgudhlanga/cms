<script setup lang="ts">
import type { AssessmentCalendarWindow } from '@/types/assessments';

const props = withDefaults(
    defineProps<{
        windows: AssessmentCalendarWindow[];
        compact?: boolean;
    }>(),
    {
        windows: () => [],
        compact: false,
    },
);

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '—';
    }

    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
};

const shouldHighlight = (window: AssessmentCalendarWindow): boolean => {
    if (window.missingCount <= 0) {
        return false;
    }

    if (window.isInNotificationWindow) {
        return true;
    }

    return window.daysRemaining !== null && window.daysRemaining <= window.firstNotificationDaysBefore;
};

const rowClass = (window: AssessmentCalendarWindow): string => {
    const highlight = shouldHighlight(window);

    if (highlight && window.severity === 'critical') {
        return 'border-rose-300 bg-rose-50';
    }

    if (highlight && window.severity === 'warning') {
        return 'border-amber-300 bg-amber-50';
    }

    if (highlight) {
        return 'border-sky-300 bg-sky-50';
    }

    return 'border-border/70 bg-muted/30';
};
</script>

<template>
    <div v-if="windows.length > 0" class="space-y-1.5">
        <p
            v-if="!compact"
            class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
        >
            {{ $tChoice('trans.assessment_calendar', 2) }}
        </p>
        <div class="space-y-1">
            <div
                v-for="window in windows"
                :key="`${window.assessmentCalendarId}-${window.assessmentTypeName}`"
                class="rounded-md border px-2 py-1.5"
                :class="rowClass(window)"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="truncate text-[11px] font-medium text-foreground">
                            {{ window.assessmentTypeName }}
                        </p>
                        <p class="text-[10px] text-muted-foreground">
                            {{ formatDate(window.startDate) }} – {{ formatDate(window.endDate) }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">
                            {{ $t('trans.assessment_calendar_notification_lecturer') }}:
                            {{ formatDate(window.firstNotificationDate) }}
                            · {{ $t('trans.assessment_calendar_notification_lecturer_vp') }}:
                            {{ formatDate(window.secondNotificationDate) }}
                            · {{ $t('trans.assessment_calendar_notification_vp') }}:
                            {{ formatDate(window.dueNotificationDate) }}
                        </p>
                    </div>
                    <div class="flex shrink-0 flex-col items-end gap-1">
                        <span
                            class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                            :class="
                                window.isOpen
                                    ? 'bg-green-50 text-green-700'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{
                                window.isOpen
                                    ? $t('dashboard.lecturer_assessment_window_open')
                                    : $t('dashboard.lecturer_assessment_window_closed')
                            }}
                        </span>
                        <span
                            v-if="window.missingCount > 0"
                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-medium text-amber-800"
                        >
                            {{ $t('assessments.dashboard_missing_count', { count: window.missingCount }) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
