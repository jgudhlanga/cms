<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { IconName, icons } from '@/lib/icons';
import {
    BILLING_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX,
    type BillingExportFilterOptions,
    type BillingExportFiltersState,
    type BillingExportPeriodOption,
} from '@/types/finance';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    periodOptions: BillingExportPeriodOption[];
    filterOptions: BillingExportFilterOptions;
    filters: BillingExportFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: BillingExportFiltersState): void;
}>();

const toSelectOption = (option: { value: number | string; label: string }): SelectOption => ({
    value: option.value,
    label: option.label,
});

const periodSelectOptions = computed<SelectOption[]>(() =>
    props.periodOptions.map(
        (period) =>
            ({
                value: Number(period.id),
                label: period.label,
            }) satisfies SelectOption,
    ),
);

const phaseOptions = computed(() => props.filterOptions.phases.map(toSelectOption));
const sourceOptions = computed(() => props.filterOptions.sources.map(toSelectOption));
const syncStatusOptions = computed(() => props.filterOptions.syncStatuses.map(toSelectOption));
const departmentOptions = computed(() => props.filterOptions.departments.map(toSelectOption));
const levelOptions = computed(() => props.filterOptions.levels.map(toSelectOption));
const courseOptions = computed(() => props.filterOptions.courses.map(toSelectOption));
const modeOptions = computed(() => props.filterOptions.modesOfStudy.map(toSelectOption));
const pastelLinkedOptions = computed(() => props.filterOptions.pastelLinked.map(toSelectOption));

const periodSelection = ref<SelectOption[]>([]);
const phaseSelection = ref<SelectOption[]>([]);
const sourceSelection = ref<SelectOption[]>([]);
const syncStatusSelection = ref<SelectOption[]>([]);
const departmentSelection = ref<SelectOption | null>(null);
const levelSelection = ref<SelectOption | null>(null);
const courseSelection = ref<SelectOption | null>(null);
const modeSelection = ref<SelectOption | null>(null);
const pastelLinkedSelection = ref<SelectOption | null>(null);
const studentNumberStartsWith = ref('');
const confirmedFrom = ref('');
const confirmedTo = ref('');
const isSyncingFromProps = ref(false);

const deriveStudentNumberPrefix = (calendarYear?: string | null): string => {
    if (!calendarYear) {
        return BILLING_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
    }

    const match = calendarYear.match(/(\d{4})/);

    return match ? match[1].slice(-2) : BILLING_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
};

const prefixForPeriodIds = (periodIds: number[]): string => {
    if (periodIds.length === 0) {
        return BILLING_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
    }

    const period = props.periodOptions.find((item) => Number(item.id) === Number(periodIds[0]));

    return deriveStudentNumberPrefix(period?.calendarYear);
};

const findOption = (options: SelectOption[], value: number | string | null | undefined): SelectOption | null => {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    return options.find((option) => String(option.value) === String(value)) ?? null;
};

const syncFromProps = (): void => {
    isSyncingFromProps.value = true;

    const selectedPeriodIds = new Set((props.filters.academic_calendar_ids ?? []).map(Number));
    periodSelection.value = periodSelectOptions.value.filter((option) => selectedPeriodIds.has(Number(option.value)));

    const selectedPhaseIds = new Set((props.filters.programme_semester_ids ?? []).map(Number));
    phaseSelection.value = phaseOptions.value.filter((option) => selectedPhaseIds.has(Number(option.value)));

    const selectedSources = new Set(props.filters.sources ?? []);
    sourceSelection.value = sourceOptions.value.filter((option) => selectedSources.has(String(option.value)));

    const selectedSyncStatuses = new Set(props.filters.sync_statuses ?? []);
    syncStatusSelection.value = syncStatusOptions.value.filter((option) =>
        selectedSyncStatuses.has(String(option.value)),
    );

    departmentSelection.value = findOption(departmentOptions.value, props.filters.institution_department_id);
    levelSelection.value = findOption(levelOptions.value, props.filters.department_level_id);
    courseSelection.value = findOption(courseOptions.value, props.filters.department_course_id);
    modeSelection.value = findOption(modeOptions.value, props.filters.mode_of_study_id);
    pastelLinkedSelection.value = findOption(pastelLinkedOptions.value, props.filters.pastel_linked ?? 'all');

    studentNumberStartsWith.value =
        props.filters.student_number_starts_with
        ?? prefixForPeriodIds(props.filters.academic_calendar_ids ?? []);
    confirmedFrom.value = props.filters.confirmed_from ?? '';
    confirmedTo.value = props.filters.confirmed_to ?? '';

    void nextTick(() => {
        isSyncingFromProps.value = false;
    });
};

const currentFilters = (): BillingExportFiltersState => ({
    academic_calendar_ids: periodSelection.value.map((option) => Number(option.value)),
    programme_semester_ids: phaseSelection.value.map((option) => Number(option.value)),
    sources: sourceSelection.value.map((option) => String(option.value)),
    sync_statuses: syncStatusSelection.value.map((option) => String(option.value)),
    student_number_starts_with: studentNumberStartsWith.value.trim(),
    confirmed_from: confirmedFrom.value || null,
    confirmed_to: confirmedTo.value || null,
    institution_department_id: departmentSelection.value?.value ? Number(departmentSelection.value.value) : null,
    department_level_id: levelSelection.value?.value ? Number(levelSelection.value.value) : null,
    department_course_id: courseSelection.value?.value ? Number(courseSelection.value.value) : null,
    mode_of_study_id: modeSelection.value?.value ? Number(modeSelection.value.value) : null,
    pastel_linked:
        pastelLinkedSelection.value?.value && String(pastelLinkedSelection.value.value) !== 'all'
            ? String(pastelLinkedSelection.value.value)
            : null,
});

const sameIds = (left: Array<string | number> = [], right: Array<string | number> = []): boolean => {
    const leftSorted = [...left].map(String).sort();
    const rightSorted = [...right].map(String).sort();

    return leftSorted.length === rightSorted.length && leftSorted.every((value, index) => value === rightSorted[index]);
};

const filtersMatch = (left: BillingExportFiltersState, right: BillingExportFiltersState): boolean => {
    return (
        sameIds(left.academic_calendar_ids, right.academic_calendar_ids)
        && sameIds(left.programme_semester_ids, right.programme_semester_ids)
        && sameIds(left.sources, right.sources)
        && sameIds(left.sync_statuses, right.sync_statuses)
        && (left.student_number_starts_with ?? '') === (right.student_number_starts_with ?? '')
        && (left.confirmed_from ?? '') === (right.confirmed_from ?? '')
        && (left.confirmed_to ?? '') === (right.confirmed_to ?? '')
        && (left.institution_department_id ?? null) === (right.institution_department_id ?? null)
        && (left.department_level_id ?? null) === (right.department_level_id ?? null)
        && (left.department_course_id ?? null) === (right.department_course_id ?? null)
        && (left.mode_of_study_id ?? null) === (right.mode_of_study_id ?? null)
        && (left.pastel_linked ?? null) === (right.pastel_linked ?? null)
    );
};

syncFromProps();

watch(
    () => [props.filters, props.periodOptions, props.filterOptions],
    () => {
        syncFromProps();
    },
    { deep: true },
);

const emitFilters = useDebounceFn((): void => {
    if (isSyncingFromProps.value) {
        return;
    }

    const next = currentFilters();

    if (filtersMatch(next, props.filters)) {
        return;
    }

    emit('change', next);
}, 400);

watch(periodSelection, (selection) => {
    if (!isSyncingFromProps.value && selection.length > 0) {
        studentNumberStartsWith.value = prefixForPeriodIds(selection.map((option) => Number(option.value)));
    }

    emitFilters();
}, { deep: true });

watch(phaseSelection, emitFilters, { deep: true });
watch(sourceSelection, emitFilters, { deep: true });
watch(syncStatusSelection, emitFilters, { deep: true });
watch(departmentSelection, emitFilters);
watch(levelSelection, emitFilters);
watch(courseSelection, emitFilters);
watch(modeSelection, emitFilters);
watch(pastelLinkedSelection, emitFilters);
watch(studentNumberStartsWith, emitFilters);
watch(confirmedFrom, emitFilters);
watch(confirmedTo, emitFilters);
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/20 p-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2"
        role="group"
        :aria-label="$t('finance.billing_export_filters_label')"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <component :is="icons[IconName.calendar]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_period') }}</span>
            <BaseCombobox
                v-model="periodSelection"
                multiple
                :options="periodSelectOptions"
                :placeholder="$t('finance.billing_export_period_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <component :is="icons[IconName.search]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_student_number_starts_with') }}</span>
            <BaseInput
                v-model="studentNumberStartsWith"
                input-id="billing_export_student_number_starts_with"
                name="student_number_starts_with"
                label=""
                :placeholder="BILLING_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <component :is="icons[IconName.filter]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_phase') }}</span>
            <BaseCombobox
                v-model="phaseSelection"
                multiple
                :options="phaseOptions"
                :placeholder="$t('finance.billing_export_phase_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_source') }}</span>
            <BaseCombobox
                v-model="sourceSelection"
                multiple
                :options="sourceOptions"
                :placeholder="$t('finance.billing_export_source_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_sync_status') }}</span>
            <BaseCombobox
                v-model="syncStatusSelection"
                multiple
                :options="syncStatusOptions"
                :placeholder="$t('finance.billing_export_sync_status_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-xs">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_confirmed_from') }}</span>
            <input
                v-model="confirmedFrom"
                type="date"
                class="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-2 text-sm text-foreground"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-xs">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_confirmed_to') }}</span>
            <input
                v-model="confirmedTo"
                type="date"
                class="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-2 text-sm text-foreground"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_department') }}</span>
            <BaseCombobox
                v-model="departmentSelection"
                :options="departmentOptions"
                :placeholder="$t('finance.billing_export_department_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_level') }}</span>
            <BaseCombobox
                v-model="levelSelection"
                :options="levelOptions"
                :placeholder="$t('finance.billing_export_level_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_course') }}</span>
            <BaseCombobox
                v-model="courseSelection"
                :options="courseOptions"
                :placeholder="$t('finance.billing_export_course_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_mode_of_study') }}</span>
            <BaseCombobox
                v-model="modeSelection"
                :options="modeOptions"
                :placeholder="$t('finance.billing_export_mode_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.billing_export_in_pastel') }}</span>
            <BaseCombobox
                v-model="pastelLinkedSelection"
                :options="pastelLinkedOptions"
                :placeholder="$t('finance.billing_export_pastel_all')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
    </div>
</template>
