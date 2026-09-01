<script setup lang="ts">
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import { IconName, icons } from '@/lib/icons';
import { DepartmentCourse } from '@/types/department-meta-data';
import { InstitutionDepartment } from '@/types/institution';
import { computed } from 'vue';

interface HeroMetric {
    labelKey: string;
    icon: IconName;
    value: string | number;
    valueClass?: string;
}

interface Props {
    course: DepartmentCourse;
    department: InstitutionDepartment;
    selectedLevelsCount?: number;
    totalLevelsCount?: number;
    configuredStructuresCount?: number;
    courseworkCaptureEnabled?: boolean | null;
    showCourseworkCapture?: boolean;
    metrics?: HeroMetric[];
}

const props = defineProps<Props>();

const metricItems = computed<HeroMetric[]>(
    () =>
        props.metrics ?? [
            {
                labelKey: 'ui_levels_offered',
                icon: IconName.school,
                value: `${props.selectedLevelsCount ?? 0}/${props.totalLevelsCount ?? 0}`,
                valueClass: 'text-indigo-600',
            },
            { labelKey: 'ui_programme_structures', icon: IconName.book_check, value: props.configuredStructuresCount ?? 0, valueClass: 'text-emerald-600' },
        ],
);
</script>

<template>
    <div class="border-border bg-card rounded-2xl border px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                <DepartmentColorSwatch
                    :color-code="department.attributes?.colorCode"
                    :department-name="department.attributes?.department"
                    size-class="h-4 w-4 rounded-md"
                />
                <h1 class="text-foreground truncate font-serif text-xl font-bold tracking-tight sm:text-2xl">
                    {{ course.attributes?.course }}
                </h1>
                <span
                    v-if="showCourseworkCapture"
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                    :class="
                        courseworkCaptureEnabled
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : 'border-border bg-muted text-muted-foreground'
                    "
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="courseworkCaptureEnabled ? 'bg-emerald-500' : 'bg-muted-foreground'" />
                    {{ $t('trans.ui_coursework_capture') }}: {{ courseworkCaptureEnabled ? $t('trans.enabled') : $t('trans.ui_disabled') }}
                </span>
            </div>
        </div>

        <p class="text-muted-foreground mt-1.5 flex items-center gap-1.5 text-xs">
            <component :is="icons[IconName.company]" class="h-3.5 w-3.5 shrink-0" />
            {{ department.attributes?.department }}
        </p>

        <div class="divide-border border-border mt-2.5 flex flex-wrap items-center divide-x border-t pt-2.5">
            <div v-for="item in metricItems" :key="item.labelKey" class="flex items-baseline gap-1.5 px-3 py-0.5 first:pl-0">
                <component :is="icons[item.icon]" class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                <span class="text-muted-foreground text-[10px] font-medium tracking-wide uppercase">{{ $t(`trans.${item.labelKey}`) }}</span>
                <span class="truncate text-sm leading-none font-semibold" :class="item.valueClass">{{ item.value }}</span>
            </div>
        </div>
    </div>
</template>
