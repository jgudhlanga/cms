<script setup lang="ts">
import TableLoading from '@/components/core/loader/TableLoading.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStaff } from '@/composables/institution/useStaff';
import { hasAbility } from '@/lib/permissions';
import { computed, onMounted } from 'vue';

interface Props {
    institutionDepartmentId: string;
}

const props = defineProps<Props>();

const { createStaffColumns, staff, loadStaff, isLoading } = useStaff();
const data = computed(() => staff.value?.staff?.data ?? []);
const trashedCount = computed(() => staff.value?.trashedCount ?? 0);
const filters = computed(() => staff.value?.filters ?? { search: '', trashed: '0' });
onMounted(() => {
    loadStaff(props.institutionDepartmentId ?? '');
});

const allowed = hasAbility('create:department-metadata');
</script>

<template>
    <TableLoading v-if="isLoading" />
    <DataTable
        v-else
        :data="data"
        :trashed-count="trashedCount"
        :filters="filters"
        :search-url="route('v1.department-metadata.staff', institutionDepartmentId)"
        :pagination="{ ...staff?.staff?.links!, ...staff?.staff?.meta! }"
        :columns="createStaffColumns()"
        :on-create="() => {}"
        :disable-create="!allowed"
    />
</template>
