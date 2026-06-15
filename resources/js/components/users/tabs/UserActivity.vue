<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';
import type { Audit } from '@/types/audit';
import type { ApiFilterResponse } from '@/types/data-pagination';
import type { User } from '@/types/users';
import { onMounted, ref } from 'vue';

interface Props {
    user: User;
}

const props = defineProps<Props>();
const { formatDate } = useUtils();

const isLoading = ref(true);
const activities = ref<Audit[]>([]);
const page = ref(1);
const hasMore = ref(false);

const loadActivities = async (): Promise<void> => {
    isLoading.value = true;

    try {
        const response = (await HttpService.get(
            `${route('v1.users.activities', { user: props.user.id })}?page=${page.value}`,
        )) as ApiFilterResponse;

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

const formatActivityLine = (activity: Audit): string => {
    const parts = [activity.attributes.description, activity.attributes.logName].filter(Boolean);

    return parts.join(' · ');
};

const formatProperties = (activity: Audit): string => {
    const properties = activity.attributes.properties;

    if (!properties || typeof properties !== 'object') {
        return '';
    }

    return Object.entries(properties)
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(', ');
};

onMounted(async () => {
    page.value = 1;
    await loadActivities();
});
</script>

<template>
    <div class="space-y-4">
        <h2 class="text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-muted-foreground">
            {{ $t('trans.activity_log') }}
        </h2>

        <DataLoadingSpinner v-if="isLoading && activities.length === 0" />

        <template v-else-if="activities.length">
            <ul class="divide-y divide-border overflow-y-auto">
                <li v-for="activity in activities" :key="activity.id" class="px-4 py-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        <span v-if="activity.attributes.createdAt">
                            {{ formatDate(activity.attributes.createdAt, 'LLL') }}
                        </span>
                        <span v-if="activity.attributes.causer">· {{ activity.attributes.causer }}</span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-foreground">
                        {{ formatActivityLine(activity) }}
                    </p>
                    <p v-if="formatProperties(activity)" class="mt-1 text-xs text-muted-foreground">
                        {{ formatProperties(activity) }}
                    </p>
                </li>
            </ul>
        </template>

        <p v-else class="text-sm italic text-muted-foreground">{{ $t('trans.not_provided') }}</p>

        <div v-if="hasMore" class="flex justify-center">
            <BaseButton :processing="isLoading" :variant="ColorVariant.shade_outline" @click="loadMore">
                {{ $t('trans.load_more') }}
            </BaseButton>
        </div>
    </div>
</template>
