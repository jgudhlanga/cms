<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import type { HostelNotice, HostelNoticeType } from '@/types/hms';
import { computed } from 'vue';

interface Props {
    notices: HostelNotice[];
    isLoading: boolean;
}

const props = defineProps<Props>();

function typeBadgeClass(type: HostelNoticeType, urgent?: boolean): string {
    if (urgent || type === 'urgent') {
        return 'border-destructive/40 bg-destructive/10 text-destructive dark:text-red-400';
    }

    if (type === 'event') {
        return 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (type === 'rule') {
        return 'border-border bg-muted/50 text-foreground';
    }

    return 'border-primary/30 bg-primary/10 text-primary';
}

const publishedNotices = computed(() =>
    props.notices.filter((n) => n.attributes.status === 'published'),
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <p class="text-left text-sm text-muted-foreground">
            {{ $t('students.accommodation_notices_count', { count: notices.length }) }}
        </p>

        <DataLoadingSpinner v-if="isLoading" />

        <div
            v-else-if="notices.length === 0"
            class="rounded-xl border border-dashed border-border py-8 text-center text-sm text-muted-foreground"
        >
            {{ $t('students.accommodation_no_notices') }}
        </div>

        <div v-else class="grid gap-3 sm:grid-cols-2">
            <article
                v-for="notice in publishedNotices.length ? publishedNotices : notices"
                :key="notice.id"
                class="rounded-xl border p-4 shadow-sm"
                :class="typeBadgeClass(notice.attributes.noticeType, notice.attributes.isUrgent)"
            >
                <div class="mb-2 flex items-start justify-between gap-2">
                    <h4 class="font-semibold text-foreground">{{ notice.attributes.title }}</h4>
                    <span class="shrink-0 rounded-full border px-2 py-0.5 text-xs font-medium capitalize">
                        {{ notice.attributes.noticeTypeLabel }}
                    </span>
                </div>
                <p class="mb-3 line-clamp-3 text-sm text-muted-foreground">{{ notice.attributes.content }}</p>
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span v-if="notice.attributes.postedByName">
                        {{ $t('students.accommodation_posted_by', { name: notice.attributes.postedByName }) }}
                    </span>
                    <span>{{ notice.attributes.publishedAt?.slice(0, 10) ?? notice.attributes.createdAt?.slice(0, 10) }}</span>
                </div>
            </article>
        </div>
    </div>
</template>
