<script setup lang="ts">
import ActivityTimelineItem from '@/components/audit/ActivityTimelineItem.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { groupActivitiesByDate, type ActivityDateLabelKind, type ActivityEventFilter } from '@/lib/activityTimeline';
import type { Audit } from '@/types/audit';
import { trans } from 'laravel-vue-i18n';
import { ChevronDown, Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        activities: Audit[];
        isLoading?: boolean;
        hasMore?: boolean;
        showCauser?: boolean;
        filter?: ActivityEventFilter;
        emptyMessage?: string;
    }>(),
    {
        isLoading: false,
        hasMore: false,
        showCauser: true,
        filter: 'all',
        emptyMessage: undefined,
    },
);

const emit = defineEmits<{
    'load-more': [];
    'update:filter': [value: ActivityEventFilter];
}>();

const filters: Array<{ value: ActivityEventFilter; labelKey: string }> = [
    { value: 'all', labelKey: 'dashboard.activity_filter_all' },
    { value: 'created', labelKey: 'dashboard.activity_created' },
    { value: 'updated', labelKey: 'dashboard.activity_updated' },
    { value: 'deleted', labelKey: 'dashboard.activity_deleted' },
];

const hasActivities = computed(() => props.activities.length > 0);
const groupedActivities = computed(() => groupActivitiesByDate(props.activities));

const groupHeading = (labelKind: ActivityDateLabelKind, dateLabel: string): string => {
    if (labelKind === 'today') {
        return trans('dashboard.activity_today');
    }

    if (labelKind === 'yesterday') {
        return trans('dashboard.activity_yesterday');
    }

    return dateLabel;
};

const setFilter = (value: ActivityEventFilter): void => {
    if (value === props.filter) {
        return;
    }

    emit('update:filter', value);
};
</script>

<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-foreground text-sm font-semibold">
                {{ $t('dashboard.recent_activity') }}
            </h2>

            <div class="flex flex-wrap items-center gap-1">
                <button
                    v-for="option in filters"
                    :key="option.value"
                    type="button"
                    class="rounded-md border px-2 py-0.5 text-[11px] font-medium transition-colors"
                    :class="
                        filter === option.value
                            ? 'border-primary/40 bg-primary/10 text-primary'
                            : 'border-border bg-background text-muted-foreground hover:bg-muted/60'
                    "
                    @click="setFilter(option.value)"
                >
                    {{ $t(option.labelKey) }}
                </button>
            </div>
        </div>

        <DataLoadingSpinner v-if="isLoading && !hasActivities" />

        <template v-else-if="hasActivities">
            <div class="space-y-5">
                <section v-for="group in groupedActivities" :key="group.key">
                    <div class="mb-0.5 flex items-center gap-3">
                        <h3 class="text-foreground text-sm font-semibold tracking-tight">
                            {{ groupHeading(group.labelKind, group.dateLabel) }}
                        </h3>
                        <div class="bg-border h-px flex-1" />
                    </div>

                    <ol>
                        <ActivityTimelineItem
                            v-for="activity in group.activities"
                            :key="activity.id"
                            :activity="activity"
                            :show-causer="showCauser"
                        />
                    </ol>
                </section>
            </div>

            <div v-if="hasMore" class="flex justify-center pt-2">
                <button
                    type="button"
                    class="border-border bg-background text-muted-foreground hover:bg-muted/70 flex h-8 w-8 items-center justify-center rounded-full border shadow-sm disabled:opacity-50"
                    :disabled="isLoading"
                    :aria-label="$t('trans.load_more')"
                    @click="emit('load-more')"
                >
                    <Loader2 v-if="isLoading" class="h-4 w-4 animate-spin" />
                    <ChevronDown v-else class="h-4 w-4" />
                </button>
            </div>
        </template>

        <p v-else class="text-muted-foreground text-sm italic">
            {{ emptyMessage || $t('dashboard.my_recent_activity_empty') }}
        </p>
    </div>
</template>
