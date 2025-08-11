<script setup lang="ts">
import { GenericButton } from '@/components/core/button';
import TableLoading from '@/components/core/loader/TableLoading.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useDepartmentCourses } from '@/composables/institution/useDepartmentCourses';
import { ColorVariant } from '@/enums/colors';
import { IconName } from '@/lib/icons';
import { hasAbility } from '@/lib/permissions';
import { computed, onMounted, ref, watch } from 'vue';
import { InstitutionDepartment } from '@/types/institution';
import { APP_MODULE_KEYS } from '@/lib/constants';
import { useModalStore } from '@/store/core/useModalStore';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();

const { createDepartmentCourseColumns, openDepartmentCoursesModal, isLoading, departmentCoursesMetaData, loadDepartmentCoursesMetaData } =
    useDepartmentCourses();
const departmentCourses = computed(() => departmentCoursesMetaData.value?.courses ?? []);
const departmentCoursesIds = computed(() => departmentCoursesMetaData.value?.departmentCoursesIds ?? []);
const departmentId = props.department?.id?.toString() ?? '';

onMounted(() => {
    loadDepartmentCoursesMetaData(departmentId);
});

const courseModalOpen = ref(false);
const { modals } = useModalStore();

watch(
  () => modals![APP_MODULE_KEYS.department_courses],
  (isOpen) => {
    if (isOpen) {
      courseModalOpen.value = true;
    } else if (courseModalOpen.value) {
      loadDepartmentCoursesMetaData(departmentId);
      courseModalOpen.value = false;
    }
  }
);

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
                :variant="ColorVariant.primary_outline"
                @click="() => openDepartmentCoursesModal(departmentCoursesIds ?? [])"
                :title="$t('trans.link_courses')"
            />
        </template>
    </DataTable>
</template>
