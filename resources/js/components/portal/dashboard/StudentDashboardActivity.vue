<script setup lang="ts">
import type { StudentPortalDashboardActivity } from '@/types/students';

interface Props {
    activities: StudentPortalDashboardActivity[];
}

defineProps<Props>();

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
    <section class="w-full min-w-0 rounded-lg border border-border bg-card px-3 py-2.5 shadow-sm">
        <div class="mb-2 flex items-center justify-between gap-2">
            <h2 class="text-sm font-semibold leading-none text-foreground">
                {{ $t('students.dashboard_activity') }}
            </h2>
            <span
                v-if="activities.length > 0"
                class="rounded bg-red-500/15 px-1.5 py-0.5 text-[9px] font-bold uppercase text-red-600 dark:text-red-400"
            >
                {{ activities.length }} {{ $t('students.dashboard_new') }}
            </span>
        </div>

        <div
            v-if="activities.length === 0"
            class="rounded-md border border-dashed border-border py-3 text-center text-xs text-muted-foreground"
        >
            {{ $t('students.dashboard_no_activity') }}
        </div>

        <ul
            v-else
            class="space-y-2"
        >
            <li
                v-for="(activity, index) in activities"
                :key="`${activity.type}-${index}`"
                class="flex items-start gap-2"
            >
                <span
                    class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full"
                    :class="dotClass(activity.severity)"
                />
                <p class="min-w-0 flex-1 wrap-break-word text-xs leading-snug text-foreground">
                    {{ activity.message }}
                </p>
            </li>
        </ul>
    </section>
</template>
