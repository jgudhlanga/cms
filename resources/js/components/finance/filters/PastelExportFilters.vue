<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import IntakePeriodComboSelect from '@/components/core/form/combobox/IntakePeriodComboSelect.vue';
import BaseInput from '@/components/core/form/text/BaseInput.vue';
import { IconName, icons } from '@/lib/icons';
import type { IntakePeriod } from '@/types/institution';
import { PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX, type PastelExportFiltersState } from '@/types/finance';
import type { WorkflowStep } from '@/types/settings';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { computed, nextTick, ref, watch } from 'vue';

interface Props {
    intakePeriods: IntakePeriod[];
    workflowSteps: WorkflowStep[];
    filters: PastelExportFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: PastelExportFiltersState): void;
}>();

const intakePeriodModel = ref<SelectOption | null>(null);
const workflowStepSelection = ref<SelectOption[]>([]);
const studentNumberStartsWith = ref('');
const isSyncingFromProps = ref(false);

const workflowStepOptions = computed<SelectOption[]>(() =>
    props.workflowSteps.map(
        (step) =>
            ({
                value: Number(step.id),
                label: step.attributes?.name ?? '',
            }) satisfies SelectOption,
    ),
);

const deriveStudentNumberPrefix = (calendarYear?: string | null): string => {
    if (!calendarYear) {
        return PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
    }

    const match = calendarYear.match(/(\d{4})/);

    return match ? match[1].slice(-2) : PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
};

const prefixForIntakePeriodId = (intakePeriodId?: number): string => {
    if (intakePeriodId === undefined || intakePeriodId === null) {
        return PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX;
    }

    const period = props.intakePeriods.find((item) => Number(item.id) === Number(intakePeriodId));

    return deriveStudentNumberPrefix(period?.attributes?.calendarYear);
};

const syncFromProps = (): void => {
    isSyncingFromProps.value = true;

    const intakePeriodId = props.filters.intake_period_id;

    intakePeriodModel.value =
        intakePeriodId !== null && intakePeriodId !== undefined
            ? (props.intakePeriods
                  .map(
                      (period) =>
                          ({
                              value: Number(period.id),
                              label: period.attributes?.name ?? '',
                          }) satisfies SelectOption,
                  )
                  .find((option) => option.value === Number(intakePeriodId)) ?? null)
            : null;

    const selectedIds = new Set((props.filters.workflow_step_ids ?? []).map(Number));

    workflowStepSelection.value = workflowStepOptions.value.filter((option) => selectedIds.has(Number(option.value)));

    studentNumberStartsWith.value =
        props.filters.student_number_starts_with
        ?? prefixForIntakePeriodId(intakePeriodId ?? undefined);

    void nextTick(() => {
        isSyncingFromProps.value = false;
    });
};

const currentFilters = (): PastelExportFiltersState => ({
    intake_period_id: intakePeriodModel.value?.value ? Number(intakePeriodModel.value.value) : undefined,
    workflow_step_ids: workflowStepSelection.value.map((option) => Number(option.value)),
    student_number_starts_with: studentNumberStartsWith.value.trim(),
});

const filtersMatch = (left: PastelExportFiltersState, right: PastelExportFiltersState): boolean => {
    if (left.intake_period_id !== right.intake_period_id) {
        return false;
    }

    if ((left.student_number_starts_with ?? '') !== (right.student_number_starts_with ?? '')) {
        return false;
    }

    const leftStepIds = [...(left.workflow_step_ids ?? [])].sort((a, b) => a - b);
    const rightStepIds = [...(right.workflow_step_ids ?? [])].sort((a, b) => a - b);

    return leftStepIds.length === rightStepIds.length && leftStepIds.every((id, index) => id === rightStepIds[index]);
};

syncFromProps();

watch(
    () => [props.filters, props.intakePeriods, props.workflowSteps],
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

watch(intakePeriodModel, (selection) => {
    if (!isSyncingFromProps.value && selection?.value) {
        studentNumberStartsWith.value = prefixForIntakePeriodId(Number(selection.value));
    }

    emitFilters();
});

watch(workflowStepSelection, emitFilters, { deep: true });
watch(studentNumberStartsWith, emitFilters);
</script>

<template>
    <div
        class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/20 p-3 sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2"
        role="group"
        :aria-label="$t('finance.pastel_export_filters_label')"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <component :is="icons[IconName.calendar]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $tChoice('trans.intake_period', 1) }}</span>
            <IntakePeriodComboSelect
                :data="intakePeriods ?? []"
                label=""
                v-model="intakePeriodModel"
                :vertical-layout="false"
                :is-required="true"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-md">
            <component :is="icons[IconName.filter]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $tChoice('trans.workflow_step', 2) }}</span>
            <BaseCombobox
                v-model="workflowStepSelection"
                multiple
                :options="workflowStepOptions"
                :placeholder="$t('finance.pastel_export_workflow_step_placeholder')"
                width-class="w-full"
                class="min-w-0 flex-1"
            />
        </div>
        <div class="flex min-w-0 flex-1 items-center gap-2 sm:min-w-55 sm:max-w-sm">
            <component :is="icons[IconName.search]" class="h-4 w-4 shrink-0 text-muted-foreground" aria-hidden="true" />
            <span class="shrink-0 text-sm font-medium text-muted-foreground">{{ $t('finance.pastel_export_student_number_starts_with') }}</span>
            <BaseInput
                v-model="studentNumberStartsWith"
                name="student_number_starts_with"
                label=""
                :placeholder="PASTEL_EXPORT_DEFAULT_STUDENT_NUMBER_PREFIX"
                class="min-w-0 flex-1"
            />
        </div>
    </div>
</template>
