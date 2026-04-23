<script setup lang="ts">
import DataTable from '@/components/core/table/DataTable.vue';
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import { useCourseSyllabuses } from '@/composables/institution/useCourseSyllabuses';
import { hasAbility } from '@/lib/permissions';
import { CourseSyllabus, InstitutionDepartment } from '@/types/institution';
import { onMounted, ref } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const institutionDepartmentId = String(props.department?.id ?? '');
const canCreate = hasAbility('departmentSetup');

const courseSyllabusList = ref<CourseSyllabus[]>([]);
const { createCourseSyllabusColumns, isLoading, listCourseSyllabuses, onCreateCourseSyllabus, courseSyllabuses } = useCourseSyllabuses();

const loadCourseSyllabuses = async () => {
    await listCourseSyllabuses(institutionDepartmentId);
    courseSyllabusList.value = (courseSyllabuses.value?.data ?? []) as CourseSyllabus[];
};

onMounted(async () => {
    await loadCourseSyllabuses();
});
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
        />
    </div>
</template>
