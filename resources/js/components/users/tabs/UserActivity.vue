<script setup lang="ts">
import ActivityTimeline from '@/components/audit/ActivityTimeline.vue';
import ActivityTrailFilters from '@/components/audit/ActivityTrailFilters.vue';
import { useActivityTrail } from '@/composables/users/useActivityTrail';
import type { ActivityEventFilter } from '@/lib/activityTimeline';
import type { User } from '@/types/users';
import { trans } from 'laravel-vue-i18n';
import { watch } from 'vue';

const props = defineProps<{
    user: User;
}>();

const { activities, emptyUsesFilterCopy, filters, hasMore, isLoading, logNameOptions, applyFilters, loadMore, resetAndLoad } = useActivityTrail(
    (params) => `${route('v1.users.activities', { user: props.user.id })}?${params.toString()}`,
    { searchable: true },
);

const onFilterChange = async (value: ActivityEventFilter): Promise<void> => {
    await applyFilters({ ...filters.value, event: value });
};

watch(
    () => props.user.id,
    async () => {
        await resetAndLoad();
    },
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <ActivityTrailFilters :filters="filters" :log-name-options="logNameOptions" @change="applyFilters" />

        <ActivityTimeline
            :activities="activities"
            :is-loading="isLoading"
            :has-more="hasMore"
            :filter="filters.event"
            :show-causer="true"
            :empty-message="emptyUsesFilterCopy ? trans('dashboard.activity_no_matches') : $t('trans.not_provided')"
            @update:filter="onFilterChange"
            @load-more="loadMore"
        />
    </div>
</template>
