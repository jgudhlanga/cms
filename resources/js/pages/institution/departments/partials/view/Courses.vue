<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import TableLoading from '@/components/core/loader/TableLoading.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { computed, onMounted } from 'vue';
import { InstitutionDepartment } from '@/types/institution';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const { createDepartmentCourseColumns, openDepartmentCoursesModal, isLoading, departmentCoursesMetaData, loadDepartmentCoursesMetaData } =
    useDepartmentCourses();
const departmentCourses = computed(() => departmentCoursesMetaData.value?.courses ?? []);
const departmentCoursesIds = computed(() => departmentCoursesMetaData.value?.departmentCoursesIds ?? []);

onMounted(() => {
    loadDepartmentCoursesMetaData(props.department?.id?.toString() ?? '');
});

const allowed = hasAbility('create:department-metadata');
</script>

<template>
    <TableLoading v-if="isLoading" />
    <DataTable v-else :data="departmentCourses ?? []" :columns="createDepartmentCourseColumns()" :show-archived-filter="false">
        <template #head-right v-if="allowed">
            <GenericButton
                :icon="IconName.add"
                class="rounded-full"
                :icon-variant="ColorVariant.white"
                :variant="ColorVariant.primary"
                @click="() => openDepartmentCoursesModal(departmentCoursesIds ?? [])"
                :title="$t('trans.link_courses')"
            />
        </template>
    </DataTable>
</template>
