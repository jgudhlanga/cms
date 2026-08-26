<script setup lang="ts">
import ActivityTimeline from '@/components/audit/ActivityTimeline.vue';
import ActivityTrailFilters from '@/components/audit/ActivityTrailFilters.vue';
import { useActivityTrail } from '@/composables/users/useActivityTrail';
import type { ActivityEventFilter } from '@/lib/activityTimeline';
import { trans } from 'laravel-vue-i18n';
import { watch } from 'vue';

const props = withDefaults(
    defineProps<{
        userId?: number | string | null;
        searchable?: boolean;
    }>(),
    {
        searchable: false,
    },
);

const { activities, emptyUsesFilterCopy, filters, hasMore, isLoading, logNameOptions, applyFilters, loadMore, resetAndLoad, searchable } =
    useActivityTrail(
        (params) => {
            if (props.userId != null && props.userId !== '') {
                return `${route('v1.users.caused-activities', { user: props.userId })}?${params.toString()}`;
            }

            return `${route('v1.me.activities')}?${params.toString()}`;
        },
        { searchable: () => props.searchable },
    );

const onFilterChange = async (value: ActivityEventFilter): Promise<void> => {
    await applyFilters({ ...filters.value, event: value });
};

watch(
    () => props.userId,
    async () => {
        await resetAndLoad();
    },
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <ActivityTrailFilters v-if="searchable" :filters="filters" :log-name-options="logNameOptions" @change="applyFilters">
            <template v-if="$slots.filtersLeading" #leading>
                <slot name="filtersLeading" />
            </template>
        </ActivityTrailFilters>

        <ActivityTimeline
            :activities="activities"
            :is-loading="isLoading"
            :has-more="hasMore"
            :filter="filters.event"
            :empty-message="emptyUsesFilterCopy ? trans('dashboard.activity_no_matches') : undefined"
            @update:filter="onFilterChange"
            @load-more="loadMore"
        />
    </div>
</template>
