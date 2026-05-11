<script setup lang="ts">
import { ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import type { StudentFiltersState } from '@/types/students';
import { IconName } from '@/enums/icons';

interface Props {
    filters: StudentFiltersState;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'change', filters: StudentFiltersState): void;
}>();

const search = ref(props.filters.search ?? '');
const name = ref(props.filters.name ?? '');
const department = ref(props.filters.department ?? '');
const level = ref(props.filters.level ?? '');
const course = ref(props.filters.course ?? '');

const applyFilters = useDebounceFn(() => {
    emit('change', {
        search: search.value || undefined,
        name: name.value || undefined,
        department: department.value || undefined,
        level: level.value || undefined,
        course: course.value || undefined,
    } as StudentFiltersState);
}, 400);

watch([search, name, department, level, course], applyFilters);

const resetFilters = () => {
    search.value = '';
    name.value = '';
    department.value = '';
    level.value = '';
    course.value = '';
};
</script>

<template>
    <div class="flex w-full gap-3">
        <!-- Search by name filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.user"
                :placeholder="$t('students.search_by_name_placeholder')"
                v-model="name"
                class="w-full rounded-full"
            />
        </div>
        <!-- Search by student details filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('students.search_by_student_details_placeholder')"
                v-model="search"
                class="w-full rounded-full"
            />
        </div>

        <!-- department filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('students.search_by_department_placeholder')"
                v-model="department"
                class="rounded-full"
            />
        </div>

        <!-- level filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('students.search_by_level_placeholder')"
                v-model="level"
                class="rounded-full"
            />
        </div>

        <!-- course filter -->
        <div class="flex flex-1">
            <BaseInputWithIcon
                :icon="IconName.search"
                :placeholder="$t('students.search_by_course_placeholder')"
                v-model="course"
                class="rounded-full"
            />
        </div>
        <div class="flex flex-wrap">
            <ResetButton @click="resetFilters" />
        </div>
    </div>
</template>