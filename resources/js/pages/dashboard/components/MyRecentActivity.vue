<script setup lang="ts">
import ActivityTimeline from '@/components/audit/ActivityTimeline.vue';
import type { ActivityEventFilter } from '@/lib/activityTimeline';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';
import type { Audit } from '@/types/audit';
import type { ApiFilterResponse } from '@/types/data-pagination';
import { onMounted, ref } from 'vue';

const isLoading = ref(true);
const activities = ref<Audit[]>([]);
const page = ref(1);
const hasMore = ref(false);
const filter = ref<ActivityEventFilter>('all');

const buildUrl = (): string => {
    const params = new URLSearchParams({ page: String(page.value) });

    if (filter.value !== 'all') {
        params.set('event', filter.value);
    }

    return `${route('v1.me.activities')}?${params.toString()}`;
};

const loadActivities = async (): Promise<void> => {
    isLoading.value = true;

    try {
        const response = (await HttpService.get(buildUrl())) as ApiFilterResponse;
        const nextPage = (response.data ?? []) as Audit[];

        activities.value = page.value === 1 ? nextPage : [...activities.value, ...nextPage];
        hasMore.value = Boolean(response.links?.next);
    } catch {
        ToastService.error('Failed to load activity log.');
    } finally {
        isLoading.value = false;
    }
};

const loadMore = async (): Promise<void> => {
    page.value += 1;
    await loadActivities();
};

const onFilterChange = async (value: ActivityEventFilter): Promise<void> => {
    filter.value = value;
    page.value = 1;
    activities.value = [];
    await loadActivities();
};

onMounted(async () => {
    page.value = 1;
    await loadActivities();
});
</script>

<template>
    <ActivityTimeline
        :activities="activities"
        :is-loading="isLoading"
        :has-more="hasMore"
        :filter="filter"
        @update:filter="onFilterChange"
        @load-more="loadMore"
    />
</template>
