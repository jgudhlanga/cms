<script setup lang="ts">
import DataLoadingSpinner from '@/components/core/loader/DataLoadingSpinner.vue';
import DataTable from '@/components/core/table/DataTable.vue';
import { useStudentApplications } from '@/composables/students/useStudentApplications';
import { useStudentPortal } from '@/composables/students/useStudentPortal';
import { Enrolment } from '@/types/enrolments';
import { onMounted, ref } from 'vue';

interface Props {
    url?: string;
}
const props = withDefaults(defineProps<Props>(), {
    url: route('v1.portal.programs'),
});

const { isLoading, getStudentData } = useStudentPortal();
const programs = ref<Enrolment[]>([]);

onMounted(async () => {
    programs.value = await getStudentData(props.url);
});

const { createStudentApplicationColumns } = useStudentApplications();
</script>
<template>
    <DataLoadingSpinner v-if="isLoading" />
    <DataTable :data="programs" :show-archived-filter="false" :columns="createStudentApplicationColumns()"> </DataTable>
</template>
