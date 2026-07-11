<script setup lang="ts">
import StudentDashboardEmptyState from '@/components/portal/dashboard/StudentDashboardEmptyState.vue';
import type { StudentPortalDashboardNotice } from '@/types/students';
import { Bell } from 'lucide-vue-next';

interface Props {
    notices?: StudentPortalDashboardNotice[];
}

withDefaults(defineProps<Props>(), {
    notices: () => [],
});

const formatDate = (value: string | null | undefined): string => {
    if (!value) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        day: 'numeric',
        month: 'short',
    }).format(new Date(value));
};
</script>

<template>
    <section class="w-full min-w-0 rounded-2xl border border-border bg-card px-4 py-4 shadow-sm sm:px-5">
        <div class="mb-3">
            <h2 class="text-base font-semibold text-foreground">
                {{ $t('students.dashboard_noticeboard') }}
            </h2>
            <p class="mt-0.5 text-sm text-muted-foreground">
                {{ $t('students.dashboard_noticeboard_description') }}
            </p>
        </div>

        <StudentDashboardEmptyState
            v-if="notices.length === 0"
            :icon="Bell"
            :title="$t('students.dashboard_noticeboard_empty_title')"
            :description="$t('students.dashboard_noticeboard_empty_description')"
        />

        <ul
            v-else
            class="max-h-56 space-y-2 overflow-y-auto"
        >
            <li
                v-for="notice in notices"
                :key="notice.id"
                class="rounded-xl border border-border/70 bg-muted/20 px-3 py-2.5"
            >
                <div class="flex min-w-0 items-start justify-between gap-2">
                    <p class="min-w-0 flex-1 wrap-break-word text-sm font-semibold leading-snug text-foreground">
                        {{ notice.title }}
                    </p>
                    <time
                        v-if="notice.publishedAt"
                        class="shrink-0 text-xs text-muted-foreground"
                    >
                        {{ formatDate(notice.publishedAt) }}
                    </time>
                </div>
                <p class="mt-1 text-sm leading-snug text-muted-foreground">
                    {{ notice.message }}
                </p>
            </li>
        </ul>
    </section>
</template>
