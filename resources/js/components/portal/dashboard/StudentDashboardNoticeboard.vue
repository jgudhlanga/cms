<script setup lang="ts">
import type { StudentPortalDashboardNotice } from '@/types/students';

interface Props {
    notices: StudentPortalDashboardNotice[];
}

defineProps<Props>();

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
    <section class="w-full min-w-0 rounded-lg border border-border bg-card px-3 py-2.5 shadow-sm">
        <div class="mb-2">
            <h2 class="text-sm font-semibold leading-none text-foreground">
                {{ $t('students.dashboard_noticeboard') }}
            </h2>
            <p class="mt-0.5 text-[11px] text-muted-foreground">
                {{ $t('students.dashboard_noticeboard_description') }}
            </p>
        </div>

        <div
            v-if="notices.length === 0"
            class="rounded-md border border-dashed border-border py-3 text-center text-xs text-muted-foreground"
        >
            {{ $t('students.dashboard_no_notices') }}
        </div>

        <ul
            v-else
            class="max-h-48 space-y-2 overflow-y-auto"
        >
            <li
                v-for="notice in notices"
                :key="notice.id"
                class="rounded-md border border-border/70 bg-muted/20 px-2.5 py-2"
            >
                <div class="flex min-w-0 items-start justify-between gap-2">
                    <p class="min-w-0 flex-1 wrap-break-word text-xs font-semibold leading-snug text-foreground">
                        {{ notice.title }}
                    </p>
                    <time
                        v-if="notice.publishedAt"
                        class="shrink-0 text-[10px] text-muted-foreground"
                    >
                        {{ formatDate(notice.publishedAt) }}
                    </time>
                </div>
                <p class="mt-0.5 text-[11px] leading-snug text-muted-foreground">
                    {{ notice.message }}
                </p>
            </li>
        </ul>
    </section>
</template>
