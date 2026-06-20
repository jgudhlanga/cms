<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useCourseSyllabuses } from '@/composables/institution/useCourseSyllabuses';
import { hasAbility } from '@/lib/permissions';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const institutionDepartmentId = computed(() => String(props.department?.id ?? ''));
const listUrl = computed(() => route('department-course-syllabuses.index', institutionDepartmentId.value));
const canCreate = hasAbility('departmentSetup');
const canImport = hasAbility('import:course-syllabuses');

const courseSyllabusList = ref<CourseSyllabus[]>([]);
const { createCourseSyllabusColumns, isLoading, listCourseSyllabuses, onCreateCourseSyllabus, courseSyllabuses } = useCourseSyllabuses();

const loadCourseSyllabuses = async () => {
    if (!institutionDepartmentId.value) {
        courseSyllabusList.value = [];
        return;
    }

    await listCourseSyllabuses(institutionDepartmentId.value);
    courseSyllabusList.value = (courseSyllabuses.value?.data ?? []) as CourseSyllabus[];
};

const loadCourseSyllabusesFromUrl = async (url: string) => {
    if (!institutionDepartmentId.value) {
        courseSyllabusList.value = [];
        return;
    }

    await listCourseSyllabuses(institutionDepartmentId.value, url);
    courseSyllabusList.value = (courseSyllabuses.value?.data ?? []) as CourseSyllabus[];
};

watch(institutionDepartmentId, loadCourseSyllabuses, { immediate: true });
</script>

<template>
    <div class="space-y-4">
        <DataLoadingSpinner v-if="isLoading" />
        <DataTable
            v-else
            :data="courseSyllabusList"
            :columns="createCourseSyllabusColumns(institutionDepartmentId)"
            :show-archived-filter="false"
            :on-create="() => onCreateCourseSyllabus(institutionDepartmentId)"
            :on-import="() => router.visit(route('department-course-syllabuses.import', institutionDepartmentId))"
            :disable-create="!canCreate"
            :disable-import="!canImport"
            :pagination="{ ...(courseSyllabuses?.links ?? {}), ...(courseSyllabuses?.meta ?? {}) }"
            :search-url="listUrl"
            :use-api="true"
            :api-fetch-action="loadCourseSyllabusesFromUrl"
        />
    </div>
</template>
