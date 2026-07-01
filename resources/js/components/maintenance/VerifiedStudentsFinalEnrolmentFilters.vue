<script setup lang="ts">
import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { BaseInputWithIcon } from '@/components/core/form';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
import { IconName } from '@/enums/icons';
import type { StudentFiltersState } from '@/types/students';
import type { VerifiedStudentsFinalEnrolmentFiltersState } from '@/types/verified-students-final-enrolment';
import { useDebounceFn } from '@vueuse/core';
import { ref, toRef, watch } from 'vue';

interface Props {
    filters: VerifiedStudentsFinalEnrolmentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: VerifiedStudentsFinalEnrolmentFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const programFilters = ref<Partial<StudentFiltersState>>({});

const emitFilters = (nextProgramFilters: Partial<StudentFiltersState>): void => {
    programFilters.value = nextProgramFilters;

    emit('change', {
        department: nextProgramFilters.department,
        level: nextProgramFilters.level,
        course: nextProgramFilters.course,
        search: search.value || undefined,
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
</script>

<template>
    <div class="flex w-full min-w-0 flex-nowrap items-center gap-3">
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="departmentSelection"
                :options="departmentOptions"
                :placeholder="$t('students.search_by_department_placeholder')"
                :on-search="async (q: string) => await whenDepartmentSearch(q)"
                :is-loading="departmentsLoading"
                class="w-full"
            />
        </div>
        <div class="min-w-0 flex-1">
            <BaseCombobox
                v-model="levelSelection"
                :options="levelOptions"
                :placeholder="$t('students.search_by_level_placeholder')"
                :on-search="async (q: string) => await whenLevelSearch(q)"
                :is-loading="levelsLoading"
                class="w-full"
            />
        </div>
        <div class="min-w-0 flex-1">
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
        <div class="min-w-0 flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                full-width
                :placeholder="$t('trans.maintenance_verified_students_final_enrolment_search_placeholder')"
                v-model="search"
                class="w-full"
            />
        </div>
    </div>
</template>
