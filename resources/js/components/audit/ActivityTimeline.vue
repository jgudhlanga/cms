<script setup lang="ts">
import { BaseButton } from '@/components/core/button';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { Badge } from '@/components/ui/badge';
import { useUtils } from '@/composables/core/useUtils';
import { ColorVariant } from '@/enums/colors';
import {
    activityEventKind,
    activityPropertyEntries,
    activitySubjectLabel,
    type ActivityEventFilter,
} from '@/lib/activityTimeline';
import type { Audit } from '@/types/audit';
import { Circle, Pencil, Plus } from 'lucide-vue-next';
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
        showCauser: false,
        filter: 'all',
        emptyMessage: undefined,
    },
);

const emit = defineEmits<{
    'load-more': [];
    'update:filter': [value: ActivityEventFilter];
}>();

const { formatDate } = useUtils();

const filters: Array<{ value: ActivityEventFilter; labelKey: string }> = [
    { value: 'all', labelKey: 'dashboard.activity_filter_all' },
    { value: 'created', labelKey: 'dashboard.activity_created' },
    { value: 'updated', labelKey: 'dashboard.activity_updated' },
];

const hasActivities = computed(() => props.activities.length > 0);

const eventKind = (activity: Audit) => activityEventKind(activity.attributes.description);

const propertyEntries = (activity: Audit) => activityPropertyEntries(activity.attributes.properties);

const subjectLabel = (activity: Audit) =>
    activitySubjectLabel(activity.attributes.subjectType) || activity.attributes.logName;

const eventBadgeClass = (kind: ReturnType<typeof activityEventKind>): string => {
    if (kind === 'created') {
        return 'border-emerald-500/30 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (kind === 'updated') {
        return 'border-primary/30 bg-primary/10 text-primary';
    }

    return 'border-transparent bg-muted text-muted-foreground';
};

const iconWrapClass = (kind: ReturnType<typeof activityEventKind>): string => {
    if (kind === 'created') {
        return 'bg-emerald-600 text-white dark:bg-emerald-700';
    }

    if (kind === 'updated') {
        return 'bg-primary text-primary-foreground';
    }

    return 'bg-muted text-muted-foreground';
};

const setFilter = (value: ActivityEventFilter): void => {
    if (value === props.filter) {
        return;
    }

    emit('update:filter', value);
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-foreground">
                {{ $t('dashboard.recent_activity') }}
            </h2>

            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    v-for="option in filters"
                    :key="option.value"
                    type="button"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-colors"
                    :class="
                        filter === option.value
                            ? 'border-primary bg-primary/10 text-primary'
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
            <ol class="relative space-y-0">
                <li
                    v-for="(activity, index) in activities"
                    :key="activity.id"
                    class="relative flex gap-3 pb-6 last:pb-0"
                >
                    <div class="relative flex w-8 shrink-0 flex-col items-center">
                        <span
                            class="z-10 flex h-8 w-8 items-center justify-center rounded-full"
                            :class="iconWrapClass(eventKind(activity))"
                        >
                            <Plus v-if="eventKind(activity) === 'created'" class="h-3.5 w-3.5" />
                            <Pencil v-else-if="eventKind(activity) === 'updated'" class="h-3.5 w-3.5" />
                            <Circle v-else class="h-3 w-3 fill-current" />
                        </span>
                        <span
                            v-if="index < activities.length - 1"
                            class="absolute top-8 bottom-0 w-px bg-border"
                            aria-hidden="true"
                        />
                    </div>

                    <div class="min-w-0 flex-1 pt-0.5">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground">
                            <span v-if="activity.attributes.createdAt">
                                {{ formatDate(activity.attributes.createdAt, 'MMM D, h:mm A') }}
                            </span>
                            <span v-if="showCauser && activity.attributes.causer">
                                · {{ activity.attributes.causer }}
                            </span>
                        </div>

                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <Badge :class="eventBadgeClass(eventKind(activity))" class="capitalize">
                                {{ activity.attributes.description || $t('dashboard.activity_other') }}
                            </Badge>
                            <span
                                v-if="subjectLabel(activity)"
                                class="text-sm font-semibold text-foreground"
                            >
                                {{ subjectLabel(activity) }}
                            </span>
                        </div>

                        <div
                            v-if="propertyEntries(activity).length"
                            class="mt-2 grid grid-cols-[minmax(0,auto)_minmax(0,1fr)] gap-x-3 gap-y-1 font-mono text-xs text-muted-foreground"
                        >
                            <template
                                v-for="entry in propertyEntries(activity)"
                                :key="`${activity.id}-${entry.key}`"
                            >
                                <span class="truncate text-muted-foreground/80">{{ entry.key }}</span>
                                <span class="min-w-0 break-all text-foreground/80">{{ entry.value }}</span>
                            </template>
                        </div>
                        <p v-else class="mt-2 text-xs italic text-muted-foreground">
                            {{ $t('dashboard.no_field_changes') }}
                        </p>
                    </div>
                </li>
            </ol>

            <div v-if="hasMore" class="flex justify-center pt-2">
                <BaseButton :processing="isLoading" :variant="ColorVariant.shade_outline" @click="emit('load-more')">
                    {{ $t('trans.load_more') }}
                </BaseButton>
            </div>
        </template>

        <p v-else class="text-sm italic text-muted-foreground">
            {{ emptyMessage || $t('dashboard.my_recent_activity_empty') }}
        </p>
    </div>
</template>
