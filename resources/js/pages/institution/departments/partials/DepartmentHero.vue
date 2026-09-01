<script setup lang="ts">
import DepartmentColorSwatch from '@/components/institution/DepartmentColorSwatch.vue';
import { Badge } from '@/components/ui/badge';
import { IconName, icons } from '@/lib/icons';
import { InstitutionDepartment } from '@/types/institution';
import { trans } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const DESCRIPTION_TRUNCATE_LENGTH = 160;

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

const isDescriptionLong = computed(() => (description.value?.length ?? 0) > DESCRIPTION_TRUNCATE_LENGTH);

const metricItems = computed(() => [
    { labelKey: 'ui_staff_count', icon: IconName.users, value: attributes.value?.staffCount ?? 0, valueClass: 'text-indigo-600' },
    { labelKey: 'ui_courses_offered', icon: IconName.book_check, value: attributes.value?.coursesOfferedCount ?? 0, valueClass: 'text-emerald-600' },
    {
        labelKey: 'head_of_department',
        icon: IconName.contact,
        value: headOfDepartment.value ?? notSet.value,
        valueClass: headOfDepartment.value ? 'text-foreground' : 'text-muted-foreground',
    },
]);
</script>

<template>
    <div class="border-border bg-card rounded-2xl border px-4 py-3 sm:px-5">
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                <DepartmentColorSwatch
                    :color-code="attributes?.colorCode"
                    :department-name="attributes?.department"
                    size-class="h-4 w-4 rounded-md"
                />
                <h1 class="text-foreground truncate font-serif text-xl font-bold tracking-tight sm:text-2xl">
                    {{ departmentTitle }}
                </h1>
                <span
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold"
                    :class="isAcademic ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-border bg-muted text-muted-foreground'"
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="isAcademic ? 'bg-emerald-500' : 'bg-muted-foreground'" />
                    {{ isAcademic ? $t('trans.academic') : $t('trans.non_academic') }}
                </span>
            </div>
        </div>

        <p v-if="division || headOfDivision" class="text-muted-foreground mt-1.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs">
            <component :is="icons[IconName.company]" class="h-3.5 w-3.5 shrink-0" />
            <span>{{ division ?? notSet }}</span>
            <template v-if="headOfDivision">
                <span class="text-border">&middot;</span>
                <span>{{ $t('trans.head_of_division') }}: {{ headOfDivision }}</span>
            </template>
        </p>

        <div class="divide-border border-border mt-2.5 flex flex-wrap items-center divide-x border-t pt-2.5">
            <div v-for="item in metricItems" :key="item.labelKey" class="flex items-baseline gap-1.5 px-3 py-0.5 first:pl-0">
                <component :is="icons[item.icon]" class="text-muted-foreground h-3.5 w-3.5 shrink-0" />
                <span class="text-muted-foreground text-[10px] font-medium tracking-wide uppercase">{{ $t(`trans.${item.labelKey}`) }}</span>
                <span class="truncate text-sm leading-none font-semibold" :class="item.valueClass">{{ item.value }}</span>
            </div>
        </div>

        <div v-if="levelsOffered.length" class="border-border mt-2.5 flex flex-wrap items-center gap-1.5 border-t pt-2.5">
            <span class="text-muted-foreground text-[10px] font-medium tracking-wide uppercase">{{ $t('trans.ui_levels_offered') }}</span>
            <Badge v-for="level in levelsOffered" :key="level" variant="secondary" class="font-normal">{{ level }}</Badge>
        </div>

        <div v-if="description" class="border-border mt-2.5 border-t pt-2.5">
            <p class="text-muted-foreground text-xs leading-relaxed" :class="!isExpanded && isDescriptionLong && 'line-clamp-2'">
                {{ description }}
            </p>
            <button
                v-if="isDescriptionLong"
                type="button"
                class="text-primary mt-1 text-xs font-medium hover:underline"
                @click="isExpanded = !isExpanded"
            >
                {{ isExpanded ? $t('trans.ui_collapse_row') : $t('trans.ui_expand_row') }}
            </button>
        </div>
    </div>
</template>
