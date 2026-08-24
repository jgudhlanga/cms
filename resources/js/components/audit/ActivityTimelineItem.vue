<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { useUtils } from '@/composables/core/useUtils';
import { activityEventKind, activityFieldChanges, activityGlyph, activitySubjectLabel, type ActivityGlyph } from '@/lib/activityTimeline';
import type { Audit } from '@/types/audit';
import { trans } from 'laravel-vue-i18n';
import { Calendar, ChevronDown, Circle, GraduationCap, LogIn, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        activity: Audit;
        showCauser?: boolean;
    }>(),
    {
        showCauser: true,
    },
);

const { formatDate } = useUtils();

const glyphIcons: Record<ActivityGlyph, typeof Plus> = {
    user: LogIn,
    calendar: Calendar,
    academic: GraduationCap,
    created: Plus,
    updated: Pencil,
    deleted: Trash2,
    other: Circle,
};

const kind = computed(() => activityEventKind(props.activity.attributes.description));
const glyph = computed(() => activityGlyph(props.activity.attributes.subjectType, kind.value));
const icon = computed(() => glyphIcons[glyph.value]);
const subject = computed(() => activitySubjectLabel(props.activity.attributes.subjectType) || props.activity.attributes.logName);
const actor = computed(() => {
    if (!props.showCauser) {
        return '';
    }

    return props.activity.attributes.causer?.trim() ?? '';
});
const verb = computed(() => {
    if (kind.value === 'created') {
        return trans('dashboard.activity_created').toLowerCase();
    }

    if (kind.value === 'updated') {
        return trans('dashboard.activity_updated').toLowerCase();
    }

    if (kind.value === 'deleted') {
        return trans('dashboard.activity_deleted').toLowerCase();
    }

    return String(props.activity.attributes.description || trans('dashboard.activity_other'))
        .trim()
        .toLowerCase();
});
const fieldDiff = computed(() => activityFieldChanges(props.activity.attributes.properties, props.activity.attributes.oldProperties));
const hasDetails = computed(() => fieldDiff.value.changed.length > 0);
const eventLabel = computed(() => {
    if (kind.value === 'created') {
        return trans('dashboard.activity_created');
    }

    if (kind.value === 'updated') {
        return trans('dashboard.activity_updated');
    }

    if (kind.value === 'deleted') {
        return trans('dashboard.activity_deleted');
    }

    return props.activity.attributes.description || trans('dashboard.activity_other');
});
const expandLabel = computed(() => (kind.value === 'updated' ? trans('dashboard.view_change') : trans('dashboard.view_details')));
const eventBadgeClass = computed((): string => {
    if (kind.value === 'created') {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400';
    }

    if (kind.value === 'updated') {
        return 'border-sky-500/20 bg-sky-500/10 text-sky-700 dark:text-sky-400';
    }

    if (kind.value === 'deleted') {
        return 'border-rose-500/20 bg-rose-500/10 text-rose-700 dark:text-rose-400';
    }

    return 'border-transparent bg-muted text-muted-foreground';
});
const metaBits = computed(() => {
    const bits: string[] = [];

    if (props.activity.attributes.createdAt) {
        bits.push(formatDate(props.activity.attributes.createdAt, 'h:mm A'));
    }

    const count = fieldDiff.value.changed.length;

    if (kind.value === 'updated' && count === 1) {
        bits.push(trans('dashboard.one_field_changed'));
    } else if (kind.value === 'updated' && count > 1) {
        bits.push(trans('dashboard.fields_changed', { count }));
    } else if ((kind.value === 'created' || kind.value === 'deleted') && count === 1) {
        bits.push(trans('dashboard.one_field'));
    } else if ((kind.value === 'created' || kind.value === 'deleted') && count > 1) {
        bits.push(trans('dashboard.field_count', { count }));
    }

    return bits;
});
</script>

<template>
    <li class="flex gap-2.5 py-2.5">
        <div class="mt-0.5 flex w-4 shrink-0 justify-center">
            <component :is="icon" class="text-muted-foreground h-4 w-4" aria-hidden="true" />
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <Badge class="h-5 rounded-md px-1.5 py-0 text-[10px] leading-none font-medium capitalize" :class="eventBadgeClass">
                    {{ eventLabel }}
                </Badge>
                <p class="text-foreground min-w-0 text-sm leading-5">
                    <span v-if="actor" class="font-semibold">{{ actor }}</span>
                    <span :class="actor ? 'text-foreground/80 font-normal' : 'font-semibold'">
                        {{ actor ? ` ${verb} ` : `${eventLabel} ` }}
                    </span>
                    <span v-if="subject" class="font-semibold">{{ subject }}</span>
                </p>
            </div>

            <p v-if="metaBits.length" class="text-muted-foreground mt-0.5 text-xs">
                <template v-for="(bit, index) in metaBits" :key="bit">
                    <span v-if="index > 0"> · </span>
                    <span>{{ bit }}</span>
                </template>
            </p>

            <Collapsible v-if="hasDetails" v-slot="{ open }" class="mt-1.5">
                <CollapsibleTrigger
                    class="border-border bg-background text-muted-foreground hover:bg-muted/60 focus-visible:ring-ring inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    {{ expandLabel }}
                    <ChevronDown class="h-3 w-3 transition-transform duration-200" :class="open && 'rotate-180'" aria-hidden="true" />
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div class="mt-2 space-y-1 text-xs">
                        <div
                            v-for="change in fieldDiff.changed"
                            :key="`${activity.id}-${change.key}`"
                            class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5"
                        >
                            <span class="text-muted-foreground">{{ change.label }}</span>
                            <span
                                v-if="change.oldValue !== null && change.oldValue !== ''"
                                class="font-mono text-red-600 line-through decoration-red-600/70 dark:text-red-400"
                            >
                                {{ change.oldValue }}
                            </span>
                            <span v-if="change.oldValue !== null && change.newValue !== null" class="text-muted-foreground"> → </span>
                            <span v-if="change.newValue !== null" class="font-mono text-emerald-600 dark:text-emerald-400">
                                {{ change.newValue }}
                            </span>
                        </div>
                        <p v-if="fieldDiff.unchangedCount" class="text-muted-foreground pt-1 text-[11px]">
                            {{ $t('dashboard.unchanged_fields_hidden', { count: fieldDiff.unchangedCount }) }}
                        </p>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </li>
</template>
