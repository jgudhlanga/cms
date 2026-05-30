<script setup lang="ts">
import type { CourseWorkAggregation } from '@/types/course-work';

withDefaults(
    defineProps<{
        aggregation: CourseWorkAggregation | undefined;
        updating?: boolean;
    }>(),
    {
        updating: false,
    },
);

const formatValue = (value: number | null | undefined): string =>
    value !== null && value !== undefined ? String(value) : '—';
</script>

<template>
    <div
        v-if="aggregation && aggregation.components.length > 0"
        class="mt-3 rounded-md border border-border/60 bg-background px-3 py-2 text-sm transition-opacity duration-200"
        :class="{ 'opacity-60': updating }"
        aria-live="polite"
        :aria-busy="updating"
    >
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <span
                v-for="component in aggregation.components"
                :key="component.assessmentTypeId"
                class="text-muted-foreground"
            >
                <span class="font-medium text-foreground">{{ component.assessmentTypeName }}</span>
                {{ formatValue(component.rawMark) }}
                <span class="text-xs">
                    ({{ $t('academic_calendar.course_work_weighted_mark') }}:
                    {{ formatValue(component.weightedMark) }})
                </span>
            </span>
            <span class="font-semibold text-foreground">
                {{ $t('academic_calendar.course_work_total_60') }}:
                {{ formatValue(aggregation.courseWorkTotal60) }}
            </span>
        </div>
    </div>
</template>
