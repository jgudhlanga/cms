<script setup lang="ts">
import { toRef } from 'vue';

import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
import { IconName } from '@/enums/icons';
import type { StudentFiltersState } from '@/types/students';

interface Props {
    filters: StudentFiltersState;
    showResetButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showResetButton: true,
});

const emit = defineEmits<{
    (e: 'change', filters: StudentFiltersState): void;
}>();

const {
    departmentSelection,
    levelSelection,
    courseSelection,
    modeOfStudySelection,
    genderSelection,
    departmentsLoading,
    modesLoading,
    courseOptions,
    coursesLoading,
    departmentOptions,
    levelOptions,
    levelsLoading,
    genderOptions,
    modeOfStudyOptions,
    selectedDepartmentIds,
    selectedLevelIds,
    whenDepartmentSearch,
    whenModeSearch,
    whenLevelSearch,
    resetFilters,
} = useStudentFilters({
    filters: toRef(props, 'filters'),
    variant: 'export',
    onChange: (filters) => emit('change', filters),
});
</script>

<template>
    <div class="flex w-full max-w-full min-w-0 flex-col gap-4">
        <!-- Row 1: department, level, course -->
        <div class="grid min-w-0 grid-cols-1 gap-3 md:grid-cols-3 md:gap-4">
            <div class="min-w-0">
                <BaseCombobox
                    v-model="departmentSelection"
                    :options="departmentOptions"
                    :placeholder="$t('students.search_by_department_placeholder')"
                    :on-search="async (q: string) => await whenDepartmentSearch(q)"
                    :is-loading="departmentsLoading"
                    class="rounded-full"
                />
            </div>
            <div class="min-w-0">
                <BaseCombobox
                    v-model="levelSelection"
                    :options="levelOptions"
                    :placeholder="$t('students.search_by_level_placeholder')"
                    :on-search="async (q: string) => await whenLevelSearch(q)"
                    :is-loading="levelsLoading"
                    class="rounded-full"
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
                    class="rounded-full"
                />
            </div>
        </div>

        <!-- Row 2: mode, gender, reset -->
        <div class="grid min-w-0 grid-cols-1 items-end gap-3 md:grid-cols-3 md:gap-4">
            <div class="min-w-0">
                <BaseCombobox
                    v-model="modeOfStudySelection"
                    multiple
                    :options="modeOfStudyOptions"
                    :placeholder="$t('students.search_by_mode_of_study_placeholder')"
                    :on-search="async (q: string) => await whenModeSearch(q)"
                    :is-loading="modesLoading"
                    class="rounded-full"
                />
            </div>
            <div class="min-w-0">
                <BaseCombobox
                    v-model="genderSelection"
                    :options="genderOptions"
                    :placeholder="$t('students.search_by_gender_placeholder')"
                    class="w-full rounded-full"
                />
            </div>
            <div v-if="showResetButton" class="min-w-0">
                <ResetButton @click="resetFilters" />
            </div>
        </div>
    </div>
</template>
