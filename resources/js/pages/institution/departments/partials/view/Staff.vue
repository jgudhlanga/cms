<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useUtils } from '@/composables/core/useUtils';
import { useStaff } from '@/composables/institution/useStaff';
import { hasAbility } from '@/lib/permissions';
import { InstitutionDepartment } from '@/types/institution';
import { computed, onMounted } from 'vue';

interface Props {
    department: InstitutionDepartment;
}

const props = defineProps<Props>();
const { navigateTo } = useUtils();
const { createStaffColumns, departmentStaff, loadDepartmentStaff, isLoading } = useStaff();
const data = computed(() => departmentStaff.value?.data ?? []);
const trashedCount = computed(() => departmentStaff.value?.trashedCount ?? 0);
const filters = computed(() => departmentStaff.value?.filters ?? { search: '', trashed: '0' });
const institutionDepartmentId = props.department.id?.toString() ?? '';
onMounted(() => {
    loadDepartmentStaff(route('v1.department-metadata.staff', institutionDepartmentId));
});
const allowed = hasAbility('create:department-metadata');
</script>

<template>
    <TableLoading v-if="isLoading" />
    <DataTable
        v-else
        :data="data"
        :use-api="true"
        :api-fetch-action="loadDepartmentStaff"
        :trashed-count="trashedCount"
        :filters="filters"
        :search-url="route('v1.department-metadata.staff', institutionDepartmentId)"
        :pagination="{ ...departmentStaff?.links!, ...departmentStaff?.meta! }"
        :columns="createStaffColumns(institutionDepartmentId)"
        :on-create="() => navigateTo(route('staff.create', institutionDepartmentId))"
        :disable-create="!allowed"
    />
</template>
