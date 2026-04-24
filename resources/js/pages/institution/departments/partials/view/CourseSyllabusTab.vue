<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useCourseSyllabuses } from '@/composables/institution/useCourseSyllabuses';
import { hasAbility } from '@/lib/permissions';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import { computed, ref, watch } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const institutionDepartmentId = computed(() => String(props.department?.id ?? ''));
const listUrl = computed(() => route('department-course-syllabuses.index', institutionDepartmentId.value));
const canCreate = hasAbility('departmentSetup');

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
            :disable-create="!canCreate"
            :pagination="{ ...(courseSyllabuses?.links ?? {}), ...(courseSyllabuses?.meta ?? {}) }"
            :search-url="listUrl"
            :use-api="true"
            :api-fetch-action="loadCourseSyllabuses"
        />
    </div>
</template>
