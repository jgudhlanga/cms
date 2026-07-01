<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { BaseInputWithIcon } from '@/components/core/form';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
import { IconName } from '@/enums/icons';
import type { StudentFiltersState } from '@/types/students';
import type {
    VerifiedStudentPaymentStatusFilter,
    VerifiedStudentsFinalEnrolmentFiltersState,
} from '@/types/verified-students-final-enrolment';
import type { SelectOption } from '@/types/utils';
import { useDebounceFn } from '@vueuse/core';
import { trans } from 'laravel-vue-i18n';
import { computed, ref, toRef, watch } from 'vue';

interface Props {
    filters: VerifiedStudentsFinalEnrolmentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: VerifiedStudentsFinalEnrolmentFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const programFilters = ref<Partial<StudentFiltersState>>({});

const paymentStatusOptions = computed<SelectOption[]>(() => [
  { value: 'all', label: trans('trans.maintenance_verified_students_final_enrolment_filter_payment_status_all') },
  { value: 'eligible', label: trans('trans.maintenance_verified_students_final_enrolment_eligible') },
  { value: 'no_payment', label: trans('trans.maintenance_verified_students_final_enrolment_no_payment') },
  {
    value: 'missing_student_number',
    label: trans('trans.maintenance_verified_students_final_enrolment_missing_student_number'),
  },
]);

const paymentStatusSelection = ref<SelectOption | null>(
  paymentStatusOptions.value.find((option) => option.value === (props.filters.payment_status ?? 'all')) ?? paymentStatusOptions.value[0],
);

const emitFilters = (nextProgramFilters: Partial<StudentFiltersState>): void => {
    programFilters.value = nextProgramFilters;

    const paymentStatus = (paymentStatusSelection.value?.value ?? 'all') as VerifiedStudentPaymentStatusFilter;

    emit('change', {
        department: nextProgramFilters.department,
        level: nextProgramFilters.level,
        course: nextProgramFilters.course,
        search: search.value || undefined,
        payment_status: paymentStatus === 'all' ? undefined : paymentStatus,
    });
};

const {
    departmentSelection,
    levelSelection,
    courseSelection,
    departmentsLoading,
    courseOptions,
    coursesLoading,
    departmentOptions,
    levelOptions,
    levelsLoading,
    selectedDepartmentIds,
    selectedLevelIds,
    whenDepartmentSearch,
    whenLevelSearch,
} = useStudentFilters({
    filters: toRef(props, 'filters'),
    variant: 'program',
    onChange: emitFilters,
});

const applySearch = useDebounceFn(() => {
    emitFilters(programFilters.value);
}, 400);

watch(search, applySearch);

const onPaymentStatusChange = (option: SelectOption | null): void => {
    paymentStatusSelection.value = option;
    emitFilters(programFilters.value);
};
</script>

<template>
    <div class="grid w-full min-w-0 grid-cols-3 gap-3">
        <div class="min-w-0">
            <BaseCombobox
                v-model="departmentSelection"
                :options="departmentOptions"
                :placeholder="$t('students.search_by_department_placeholder')"
                :on-search="async (q: string) => await whenDepartmentSearch(q)"
                :is-loading="departmentsLoading"
                class="w-full"
            />
        </div>
        <div class="min-w-0">
            <BaseCombobox
                v-model="levelSelection"
                :options="levelOptions"
                :placeholder="$t('students.search_by_level_placeholder')"
                :on-search="async (q: string) => await whenLevelSearch(q)"
                :is-loading="levelsLoading"
                class="w-full"
            />
        </div>
        <div class="min-w-0">
            <BaseCombobox
                v-model="courseSelection"
                multiple
                :options="courseOptions"
                :placeholder="$t('students.search_by_course_placeholder')"
                :is-loading="coursesLoading"
                :disabled="!selectedDepartmentIds.length || !selectedLevelIds.length"
                class="w-full"
            />
        </div>
        <div class="min-w-0">
            <BaseInputWithIcon
                :icon="IconName.search"
                full-width
                :placeholder="$t('trans.maintenance_verified_students_final_enrolment_search_placeholder')"
                v-model="search"
                class="w-full"
            />
        </div>
        <div class="min-w-0">
            <BaseCombobox
                :model-value="paymentStatusSelection"
                :options="paymentStatusOptions"
                :placeholder="$t('trans.maintenance_verified_students_final_enrolment_filter_payment_status')"
                class="w-full"
                @update:model-value="onPaymentStatusChange"
            />
        </div>
        <div class="flex min-w-0 items-center justify-start">
            <slot name="actions" />
        </div>
    </div>
</template>
