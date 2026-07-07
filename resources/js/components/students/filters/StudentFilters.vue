<script setup lang="ts">
import { computed, toRef } from 'vue';

import BaseCombobox from '@/components/core/form/combobox/BaseCombobox.vue';
import GenericButton from '@/components/core/button/GenericButton.vue';
import { useStudentFilters } from '@/composables/students/useStudentFilters';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/enums/icons';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { hasAbility } from '@/lib/permissions';
import { useModalStore } from '@/store/core/useModalStore';
import type { StudentFiltersState } from '@/types/students';

interface Props {
    filters: StudentFiltersState;
    showExportButton?: boolean;
    showResetButton?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showExportButton: false,
    showResetButton: true,
});

const emit = defineEmits<{
    (e: 'change', filters: StudentFiltersState): void;
}>();

const { openModal } = useModalStore();

const canExportStudents = computed(() => props.showExportButton && hasAbility('export:students'));

const openExportModal = (): void => {
    openModal(APP_MODULE_KEYS.student_list_export);
};

const {
    search,
    name,
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
    resetFilters,
} = useStudentFilters({
    filters: toRef(props, 'filters'),
    variant: 'index',
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

        <!-- Row 2: name, student details search, actions -->
        <div class="grid min-w-0 grid-cols-1 items-end gap-3 md:grid-cols-3 md:gap-4">
            <div class="min-w-0">
                <BaseInputWithIcon
                    :icon="IconName.user"
                    full-width
                    :placeholder="$t('students.search_by_name_placeholder')"
                    v-model="name"
                    class="w-full"
                />
            </div>
            <div class="min-w-0">
                <BaseInputWithIcon
                    :icon="IconName.search"
                    full-width
                    :placeholder="$t('students.search_by_student_details_placeholder')"
                    v-model="search"
                    class="w-full"
                />
            </div>
            <div v-if="showResetButton || canExportStudents" class="flex min-w-0 items-center justify-end space-x-2">
                <ResetButton v-if="showResetButton" @click="resetFilters" />
                <GenericButton
                    v-if="canExportStudents"
                    :icon="IconName.export"
                    :variant="ColorVariant.primary"
                    :title="$t('trans.export')"
                    class="rounded-full"
                    @click="openExportModal"
                />
            </div>
        </div>
    </div>
</template>
