<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DashboardCard from '@/pages/dashboard/components/DashboardCard.vue';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import HttpService from '@/services/http.service';
import ToastService from '@/services/toast.service';
import type { Audit } from '@/types/audit';
import type { ApiFilterResponse } from '@/types/data-pagination';
import { onMounted, ref } from 'vue';

const { formatDate } = useUtils();

const isLoading = ref(true);
const activities = ref<Audit[]>([]);
const page = ref(1);
const hasMore = ref(false);

const SENSITIVE_PROPERTY_KEYS = new Set([
    'password',
    'password_confirmation',
    'current_password',
    'remember_token',
]);

const loadActivities = async (): Promise<void> => {
    isLoading.value = true;

    try {
        const response = (await HttpService.get(
            `${route('v1.me.activities')}?page=${page.value}`,
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
        .filter(([key]) => !SENSITIVE_PROPERTY_KEYS.has(key.toLowerCase()))
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(', ');
};

onMounted(async () => {
    page.value = 1;
    await loadActivities();
});
</script>

<template>
    <DashboardCard :title="$t('dashboard.my_recent_activity')">
        <DataLoadingSpinner v-if="isLoading && activities.length === 0" />

        <template v-else-if="activities.length">
            <ul class="max-h-80 divide-y divide-border overflow-y-auto">
                <li v-for="activity in activities" :key="activity.id" class="px-1 py-3">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                        <span v-if="activity.attributes.createdAt">
                            {{ formatDate(activity.attributes.createdAt, 'LLL') }}
                        </span>
                        <span v-if="activity.attributes.subjectType">
                            · {{ activity.attributes.subjectType }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm font-medium text-foreground">
                        {{ formatActivityLine(activity) }}
                    </p>
                    <p v-if="formatProperties(activity)" class="mt-1 text-xs text-muted-foreground">
                        {{ formatProperties(activity) }}
                    </p>
                </li>
            </ul>

            <div v-if="hasMore" class="mt-3 flex justify-center">
                <BaseButton :processing="isLoading" :variant="ColorVariant.shade_outline" @click="loadMore">
                    {{ $t('trans.load_more') }}
                </BaseButton>
            </div>
        </template>

        <p v-else class="text-sm italic text-muted-foreground">
            {{ $t('dashboard.my_recent_activity_empty') }}
        </p>
    </DashboardCard>
</template>
