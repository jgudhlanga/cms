<script setup lang="ts">
import { toRef } from 'vue';

import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
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
    <div class="flex w-full max-w-full min-w-0 flex-col gap-3">
        <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
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
            <div class="min-w-0 sm:col-span-2 lg:col-span-1">
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

        <div
            class="grid min-w-0 grid-cols-1 items-center gap-3 sm:grid-cols-2"
            :class="showResetButton ? 'lg:grid-cols-[1fr_1fr_auto]' : 'lg:grid-cols-2'"
        >
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
            <div
                v-if="showResetButton"
                class="flex min-w-0 items-center justify-start sm:col-span-2 lg:col-span-1 lg:justify-end"
            >
                <ResetButton @click="resetFilters" />
            </div>
        </div>
    </div>
</template>
