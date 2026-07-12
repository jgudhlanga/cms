<script setup lang="ts">
import StudentDashboardEmptyState from '@/components/portal/dashboard/StudentDashboardEmptyState.vue';
import type { StudentPortalDashboardActivity } from '@/types/students';
import { Clock } from 'lucide-vue-next';

interface Props {
    activities?: StudentPortalDashboardActivity[];
}

withDefaults(defineProps<Props>(), {
    activities: () => [],
});

const dotClass = (severity: StudentPortalDashboardActivity['severity']): string => {
    if (severity === 'warning') {
        return 'bg-amber-500';
    }

    if (severity === 'success') {
        return 'bg-emerald-500';
    }

    return 'bg-blue-500';
};
</script>

<template>
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card px-4 py-4 shadow-sm sm:px-5">
        <div class="mb-3 flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-foreground">
                    {{ $t('students.dashboard_activity') }}
                </h2>
                <p class="mt-0.5 text-sm text-muted-foreground">
                    {{ $t('students.dashboard_activity_description') }}
                </p>
            </div>
            <span
                v-if="activities.length > 0"
                class="shrink-0 rounded bg-red-500/15 px-1.5 py-0.5 text-[10px] font-bold uppercase text-red-600 dark:text-red-400"
            >
                {{ activities.length }} {{ $t('students.dashboard_new') }}
            </span>
        </div>

        <StudentDashboardEmptyState
            v-if="activities.length === 0"
            :icon="Clock"
            :title="$t('students.dashboard_activity_empty_title')"
            :description="$t('students.dashboard_activity_empty_description')"
        />

        <ul
            v-else
            class="space-y-2.5"
        >
            <li
                v-for="(activity, index) in activities"
                :key="`${activity.type}-${index}`"
                class="flex items-start gap-2"
            >
                <span
                    class="mt-1.5 size-1.5 shrink-0 rounded-full"
                    :class="dotClass(activity.severity)"
                />
                <p class="min-w-0 flex-1 wrap-break-word text-sm leading-snug text-foreground">
                    {{ activity.message }}
                </p>
            </li>
        </ul>
    </section>
</template>
