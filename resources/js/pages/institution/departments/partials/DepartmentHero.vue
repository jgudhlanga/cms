<script setup lang="ts">
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import { IconName, icons } from '@/lib/icons';
import { InstitutionDepartment } from '@/types/institution';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const LEVELS_PREVIEW_COUNT = 6;

const isExpanded = ref(false);

const attributes = computed(() => props.department.attributes);

const isAcademic = computed(() => Number(attributes.value?.isAcademic) === 1);

const departmentTitle = computed(() => {
    const code = attributes.value?.departmentCode?.trim();

    return code ? `${attributes.value?.department} (${code})` : attributes.value?.department;
});

const notSet = computed(() => trans('trans.not_set'));

const division = computed(() => attributes.value?.division?.trim() || null);
const headOfDivision = computed(() => attributes.value?.headOfDivision?.trim() || null);
const headOfDepartment = computed(() => attributes.value?.headOfDepartment?.trim() || null);
const levelsOffered = computed(() => attributes.value?.levelsOffered ?? []);
const description = computed(() => attributes.value?.description?.trim() || null);

// Only the counts earn a slot on the headline row — they're short and scannable.
// Everything with a person or place name in it drops to the meta line below.
const metricChips = computed(() => [
    { key: 'staff', icon: IconName.users, value: attributes.value?.staffCount ?? 0, labelKey: 'ui_staff_count', iconClass: 'text-indigo-500' },
    {
        key: 'courses',
        icon: IconName.book_check,
        value: attributes.value?.coursesOfferedCount ?? 0,
        labelKey: 'ui_courses_offered',
        iconClass: 'text-emerald-500',
    },
]);

interface MetaItem {
    key: string;
    icon: IconName;
    labelKey?: string;
    value: string;
    muted?: boolean;
}

const metaItems = computed<MetaItem[]>(() =>
    [
        { key: 'division', icon: IconName.company, value: division.value ?? notSet.value, muted: !division.value },
        headOfDivision.value ? { key: 'head_of_division', icon: IconName.contact, labelKey: 'head_of_division', value: headOfDivision.value } : null,
        {
            key: 'head_of_department',
            icon: IconName.contact,
            labelKey: 'head_of_department',
            value: headOfDepartment.value ?? notSet.value,
            muted: !headOfDepartment.value,
        },
    ].filter((item): item is MetaItem => item !== null),
);

// Long level lists used to force their own row; show a preview and fold the rest
// into the same disclosure the description uses.
const previewLevels = computed(() => (isExpanded.value ? levelsOffered.value : levelsOffered.value.slice(0, LEVELS_PREVIEW_COUNT)));
const hiddenLevelsCount = computed(() => Math.max(levelsOffered.value.length - LEVELS_PREVIEW_COUNT, 0));

const isCollapsible = computed(() => Boolean(description.value) || hiddenLevelsCount.value > 0);
</script>

<template>
    <div class="border-border bg-card rounded-xl border px-3 py-2.5 sm:px-4">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            <div class="flex min-w-0 flex-1 items-center gap-2">
                <DepartmentColorSwatch
                    :color-code="attributes?.colorCode"
                    :department-name="attributes?.department"
                    size-class="h-3.5 w-3.5 rounded"
                />
                <h1 class="text-foreground truncate font-serif text-lg font-bold tracking-tight sm:text-xl">
                    {{ departmentTitle }}
                </h1>
                <span
                    class="inline-flex shrink-0 items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase"
                    :class="isAcademic ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-border bg-muted text-muted-foreground'"
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="isAcademic ? 'bg-emerald-500' : 'bg-muted-foreground'" />
                    {{ isAcademic ? $t('trans.academic') : $t('trans.non_academic') }}
                </span>
            </div>

            <div class="flex shrink-0 items-center gap-1.5">
                <span
                    v-for="metric in metricChips"
                    :key="metric.key"
                    class="border-border bg-muted/40 inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs"
                >
                    <component :is="icons[metric.icon]" class="h-3.5 w-3.5 shrink-0" :class="metric.iconClass" />
                    <span class="text-foreground font-semibold tabular-nums">{{ metric.value }}</span>
                    <span class="text-muted-foreground hidden sm:inline">{{ $t(`trans.${metric.labelKey}`) }}</span>
                </span>
                <button
                    v-if="isCollapsible"
                    type="button"
                    class="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring/50 inline-flex h-7 w-7 items-center justify-center rounded-md transition-colors focus-visible:ring-[3px] focus-visible:outline-none"
                    :aria-expanded="isExpanded"
                    :aria-label="isExpanded ? $t('trans.ui_collapse_row') : $t('trans.ui_expand_row')"
                    @click="isExpanded = !isExpanded"
                >
                    <component :is="icons[IconName.chevron_down]" class="h-4 w-4 transition-transform" :class="isExpanded && 'rotate-180'" />
                </button>
            </div>
        </div>

        <div class="border-border mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 border-t pt-2 text-xs">
            <template v-for="(item, index) in metaItems" :key="item.key">
                <span v-if="index" class="text-border select-none">&middot;</span>
                <span class="text-muted-foreground flex min-w-0 items-center gap-1.5">
                    <component :is="icons[item.icon]" class="h-3.5 w-3.5 shrink-0" />
                    <span v-if="item.labelKey" class="text-[10px] font-medium tracking-wide uppercase">{{ $t(`trans.${item.labelKey}`) }}</span>
                    <span class="truncate" :class="item.muted ? 'text-muted-foreground italic' : 'text-foreground font-medium'">{{
                        item.value
                    }}</span>
                </span>
            </template>

            <template v-if="levelsOffered.length">
                <span class="text-border select-none">&middot;</span>
                <span class="flex flex-wrap items-center gap-1">
                    <span class="text-muted-foreground text-[10px] font-medium tracking-wide uppercase">{{ $t('trans.ui_levels_offered') }}</span>
                    <span
                        v-for="level in previewLevels"
                        :key="level"
                        class="border-border bg-muted/50 text-foreground rounded px-1.5 py-0.5 text-[11px] leading-none font-medium"
                    >
                        {{ level }}
                    </span>
                    <span v-if="!isExpanded && hiddenLevelsCount" class="text-muted-foreground text-[11px] font-medium">
                        +{{ hiddenLevelsCount }}
                    </span>
                </span>
            </template>
        </div>

        <p v-if="description && isExpanded" class="text-muted-foreground border-border mt-2 border-t pt-2 text-xs leading-relaxed">
            {{ description }}
        </p>
    </div>
</template>
